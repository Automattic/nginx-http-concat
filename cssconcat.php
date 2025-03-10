<?php
/*
Plugin Name: CSS Concat
Plugin URI: http://wp-plugins.org/#
Description: Concatenates CSS
Author: Automattic
Version: 0.01
Author URI: http://automattic.com/
 */

require_once( dirname( __FILE__ ) . '/concat-utils.php' );

if ( ! defined( 'ALLOW_GZIP_COMPRESSION' ) )
	define( 'ALLOW_GZIP_COMPRESSION', true );

class WPcom_CSS_Concat extends WP_Styles {
	private $old_styles;
	public $allow_gzip_compression;

	function __construct( $styles ) {
		if ( empty( $styles ) || ! ( $styles instanceof WP_Styles ) ) {
			$this->old_styles = new WP_Styles();
		} else {
			$this->old_styles = $styles;
		}

		// Unset all the object properties except our private copy of the styles object.
		// We have to unset everything so that the overload methods talk to $this->old_styles->whatever
		// instead of $this->whatever.
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {
			if ( 'old_styles' === $key ) {
				continue;
			}
			unset( $this->$key );
		}
	}

	function do_items( $handles = false, $group = false ) {
		/*
		 * When in an AMP response, prevent needlessly concatenating and minifying CSS because the AMP plugin does this automatically;
		 * beyond this, the AMP plugin also does tree shaking to strip out CSS rules that do not apply to the current page, and the
		 * resulting CSS from external stylesheets, style elements, and style attributes is then all combined into the one style[amp-custom].
		 * This check prevents the AMP plugin from having to issue an HTTP request to fetch the contents of the concatenated stylesheet.
		 *
		 * Note that this is_amp_endpoint() check is done here at runtime instead of in css_concat_init() because it must be called
		 * after the parse_query action in order to determine whether the response will be served as AMP or not. Note also that
		 * this change only applies to sites that enable AMP theme support, as the classic AMP post templates do not use WP_Styles at all.
		 * See <https://github.com/Automattic/amp-wp/wiki/Adding-Theme-Support>.
		 */
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return $this->old_styles->do_items( $handles, $group );
		}

		$handles = false === $handles ? $this->queue : (array) $handles;
		$stylesheets = array();
		$siteurl = apply_filters( 'ngx_http_concat_site_url', $this->base_url );

		$this->all_deps( $handles );

		$stylesheet_group_index = 0;
		foreach( $this->to_do as $key => $handle ) {
			$obj = $this->registered[$handle];
			$obj->src = apply_filters( 'style_loader_src', $obj->src, $obj->handle );

			// Core is kind of broken and returns "true" for src of "colors" handle
			// http://core.trac.wordpress.org/attachment/ticket/16827/colors-hacked-fixed.diff
			// http://core.trac.wordpress.org/ticket/20729
			$css_url = $obj->src;
			if ( 'colors' == $obj->handle && true === $css_url ) {
				$css_url = wp_style_loader_src( $css_url, $obj->handle );
			}

			$css_url_parsed = parse_url( is_string( $obj->src ) ? $obj->src : '' );
			$extra = $obj->extra;

			// Don't concat by default
			$do_concat = false;

			// Only try to concat static css files
			if ( false !== strpos( $css_url_parsed['path'], '.css' ) )
				$do_concat = true;

			// Don't try to concat styles which are loaded conditionally (like IE stuff)
			if ( isset( $extra['conditional'] ) )
				$do_concat = false;

			// Don't concat rtl stuff for now until concat supports it correctly
			if ( 'rtl' === $this->text_direction && ! empty( $extra['rtl'] ) )
				$do_concat = false;

			// Don't try to concat externally hosted scripts
			$is_internal_url = WPCOM_Concat_Utils::is_internal_url( $css_url, $siteurl );
			if ( ! $is_internal_url ) {
				$do_concat = false;
			}

			// Concat and canonicalize the paths only for
			// existing scripts that aren't outside ABSPATH
			$css_realpath = WPCOM_Concat_Utils::realpath( $css_url, $siteurl );
			if ( ! $css_realpath || 0 !== strpos( $css_realpath, ABSPATH ) )
				$do_concat = false;
			else
				$css_url_parsed['path'] = substr( $css_realpath, strlen( ABSPATH ) - 1 );

			// Allow plugins to disable concatenation of certain stylesheets.
			$do_concat = apply_filters( 'css_do_concat', $do_concat, $handle );

			if ( true === $do_concat ) {
				$media = $obj->args;
				if( empty( $media ) )
					$media = 'all';
				if ( ! isset( $stylesheets[ $stylesheet_group_index ] ) || ( isset( $stylesheets[ $stylesheet_group_index ] ) && ! is_array( $stylesheets[ $stylesheet_group_index ] ) ) )
					$stylesheets[ $stylesheet_group_index ] = array();

				$stylesheets[ $stylesheet_group_index ][ $media ][ $handle ] = $css_url_parsed['path'];

				if ( count( $stylesheets[ $stylesheet_group_index ][ $media ] ) >= WPCOM_Concat_Utils::get_concat_max() ) {
					$stylesheet_group_index++;
				}
				$this->done[] = $handle;
			} else {
				$stylesheet_group_index++;
				$stylesheets[ $stylesheet_group_index ][ 'noconcat' ][] = $handle;
				$stylesheet_group_index++;
			}
			unset( $this->to_do[$key] );
		}

		foreach( $stylesheets as $idx => $stylesheets_group ) {
			foreach( $stylesheets_group as $media => $css ) {
				if ( 'noconcat' == $media ) {

					foreach( $css as $handle ) {
						if ( $this->do_item( $handle, $group ) )
							$this->done[] = $handle;
					}
					continue;
				} elseif ( count( $css ) > 1) {
					$paths = array_map( function( $url ) { return ABSPATH . $url; }, $css );
					$mtime = max( array_map( 'filemtime', $paths ) );
					$path_str = implode( ',', $css ) . "?m={$mtime}";

					if ( $this->allow_gzip_compression ) {
						$path_64 = base64_encode( gzcompress( $path_str ) );
						if ( strlen( $path_str ) > ( strlen( $path_64 ) + 1 ) )
							$path_str = '-' . $path_64;
					}

					$href = $siteurl . "/_static/??" . $path_str;
				} else {
					$href = $this->cache_bust_mtime( $siteurl . current( $css ), $siteurl );
				}

				$handles = array_keys( $css );
				echo apply_filters( 'ngx_http_concat_style_loader_tag', "<link rel='stylesheet' id='$media-css-$idx' href='$href' type='text/css' media='$media' />\n", $handles, $href, $media );
				array_map( array( $this, 'print_inline_style' ), array_keys( $css ) );
			}
		}
		return $this->done;
	}

	function cache_bust_mtime( $url, $siteurl ) {
		if ( strpos( $url, '?m=' ) )
			return $url;

		$parts = parse_url( $url );
		if ( ! isset( $parts['path'] ) || empty( $parts['path'] ) )
			return $url;

		$file = WPCOM_Concat_Utils::realpath( $url, $siteurl );

		$mtime = false;
		if ( file_exists( $file ) )
			$mtime = filemtime( $file );

		if ( ! $mtime )
			return $url;

		if ( false === strpos( $url, '?' ) ) {
			$q = '';
		} else {
			list( $url, $q ) = explode( '?', $url, 2 );
			if ( strlen( $q ) )
				$q = '&amp;' . $q;
		}

		return "$url?m={$mtime}g{$q}";
	}

	function __isset( $key ) {
		return isset( $this->old_styles->$key );
	}

	function __unset( $key ) {
		unset( $this->old_styles->$key );
	}

	function &__get( $key ) {
		return $this->old_styles->$key;
	}

	function __set( $key, $value ) {
		$this->old_styles->$key = $value;
	}
}

function css_concat_init() {
	global $wp_styles;

	$wp_styles = new WPcom_CSS_Concat( $wp_styles );
	$wp_styles->allow_gzip_compression = ALLOW_GZIP_COMPRESSION;
}

add_action( 'init', 'css_concat_init' );
