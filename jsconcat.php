<?php
/*
Plugin Name: JS Concat
Plugin URI: http://wp-plugins.org/#
Description: Concatenates JS
Author: Automattic
Version: 0.01
Author URI: http://automattic.com/
 */

require_once( dirname( __FILE__ ) . '/concat-utils.php' );

if ( ! defined( 'ALLOW_GZIP_COMPRESSION' ) )
	define( 'ALLOW_GZIP_COMPRESSION', true );

class WPcom_JS_Concat extends WP_Scripts {
	private $old_scripts;
	public $allow_gzip_compression;

	function __construct( $scripts ) {
		if ( empty( $scripts ) || ! ( $scripts instanceof WP_Scripts ) ) {
			$this->old_scripts = new WP_Scripts();
		} else {
			$this->old_scripts = $scripts;
		}

		// Unset all the object properties except our private copy of the scripts object.
		// We have to unset everything so that the overload methods talk to $this->old_scripts->whatever
		// instead of $this->whatever.
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {
			if ( 'old_scripts' === $key ) {
				continue;
			}
			unset( $this->$key );
		}
	}

	protected function has_inline_content( $handle ) {
		$before_output = $this->get_data( $handle, 'before' );
		if ( ! empty( $before_output ) ) {
			return true;
		}

		$after_output = $this->get_data( $handle, 'after' );
		if ( ! empty( $after_output ) ) {
			return true;
		}

		// JavaScript translations
		$has_translations = ! empty( $this->registered[ $handle ]->textdomain );
		if ( $has_translations ) {
			return true;
		}

		return false;
	}

	function do_items( $handles = false, $group = false ) {
		$handles = false === $handles ? $this->queue : (array) $handles;
		$javascripts= array();
		$siteurl = apply_filters( 'ngx_http_concat_site_url', $this->base_url );

		$this->all_deps( $handles );
		$level = 0;

		foreach( $this->to_do as $key => $handle ) {
			if ( in_array( $handle, $this->done ) || !isset( $this->registered[$handle] ) )
				continue;

			if ( 0 === $group && $this->groups[$handle] > 0 ) {
				$this->in_footer[] = $handle;
				unset( $this->to_do[$key] );
				continue;
			}

			if ( ! $this->registered[$handle]->src ) { // Defines a group.
				if ( $this->do_item( $handle, $group ) ) {
					$this->done[] = $handle;
				}

				continue;
			}

			if ( false === $group && in_array( $handle, $this->in_footer, true ) )
				$this->in_footer = array_diff( $this->in_footer, (array) $handle );

			$obj = $this->registered[$handle];
			$js_url = $obj->src;
			$js_url_parsed = parse_url( $js_url );
			$extra = $obj->extra;

			// Don't concat by default
			$do_concat = false;

			// Only try to concat static js files
			if ( false !== strpos( $js_url_parsed['path'], '.js' ) )
				$do_concat = true;

			// Don't try to concat externally hosted scripts
			$is_internal_url = WPCOM_Concat_Utils::is_internal_url( $js_url, $siteurl );
			if ( ! $is_internal_url ) {
				$do_concat = false;
			}

			// Concat and canonicalize the paths only for
			// existing scripts that aren't outside ABSPATH
			$js_realpath = WPCOM_Concat_Utils::realpath( $js_url, $siteurl );
			if ( ! $js_realpath || 0 !== strpos( $js_realpath, ABSPATH ) )
				$do_concat = false;
			else
				$js_url_parsed['path'] = substr( $js_realpath, strlen( ABSPATH ) - 1 );

			if ( $this->has_inline_content( $handle ) ) {
				$do_concat = false;
			}

			// Skip core scripts that use Strict Mode
			if ( 'react' === $handle || 'react-dom' === $handle ) {
				$do_concat = false;
			}

			// Only allow filter for anything that is currently concat'ing
			// Scripts which are not concat'able should stay that way
			if ( true === $do_concat ) {
				$do_concat = apply_filters( 'js_do_concat', $do_concat, $handle );
			}

			if ( true === $do_concat ) {
				if ( !isset( $javascripts[$level] ) )
					$javascripts[$level]['type'] = 'concat';

				$javascripts[$level]['paths'][] = $js_url_parsed['path'];
				$javascripts[$level]['handles'][] = $handle;

				if ( count( $javascripts[$level]['paths'] ) >= WPCOM_Concat_Utils::get_concat_max() ) {
					$level++;
				}

			} else {
				$level++;
				$javascripts[$level]['type'] = 'do_item';
				$javascripts[$level]['handle'] = $handle;
				$level++;
			}
			unset( $this->to_do[$key] );
		}

		if ( empty( $javascripts ) )
			return $this->done;

		foreach ( $javascripts as $js_array ) {
			if ( 'do_item' == $js_array['type'] ) {
				if ( $this->do_item( $js_array['handle'], $group ) )
					$this->done[] = $js_array['handle'];
			} else if ( 'concat' == $js_array['type'] ) {
				array_map( array( $this, 'print_extra_script' ), $js_array['handles'] );

				if ( isset( $js_array['paths'] ) && count( $js_array['paths'] ) > 1) {
					$paths = array_map( function( $url ) { return ABSPATH . $url; }, $js_array['paths'] );
					$mtime = max( array_map( 'filemtime', $paths ) );
					$path_str = implode( ',', $js_array['paths'] ) . "?m={$mtime}j";

					if ( $this->allow_gzip_compression ) {
						$path_64 = base64_encode( gzcompress( $path_str ) );
						if ( strlen( $path_str ) > ( strlen( $path_64 ) + 1 ) )
							$path_str = '-' . $path_64;
					}

					$href = $siteurl . "/_static/??" . $path_str;
				} elseif ( isset( $js_array['paths'] ) && is_array( $js_array['paths'] ) ) {
					$href = $this->cache_bust_mtime( $siteurl . $js_array['paths'][0], $siteurl );
				}

				$this->done = array_merge( $this->done, $js_array['handles'] );

				// Print before/after scripts from wp_inline_scripts() and concatenated script tag
				if ( isset( $js_array['extras']['before'] ) ) {
					foreach ( $js_array['extras']['before'] as $inline_before ) {
						echo $inline_before;
					}
				}
			
				// Allowed attributes taken from: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script
				$allowed_attributes = array( 
					'async', 
					'defer', 
					'nomodule', 
					'crossorigin', 
					'integrity', 
					'type', 
					'nonce', 
					'referrerpolicy'
				);
				$attr_string = '';
				/**
				 * Allow adding extra arguments for the script tag.
				 * Either associative array or regular array.
				 * E.g.
				 * [ 'async', 'defer', 'nonce' => '$random_generated_number' ]
				 *
				 * @param string $href URL for the script.
				 * @param array $js_array array that contains the type, path, and handle for the scripts being processed.
				 * @param WPcom_JS_Concat this instance of WPcom_JS_Concat.
				 */
				foreach ( (array) apply_filters( 'js_concat_script_attributes', [], $href, $js_array, $this ) as $k => $v ) {
					if ( is_int( $k ) && in_array( $v, $allowed_attributes ) ) {
						$attr_string .= sprintf( ' %s', esc_attr( $v ) );	
					} else if ( array_search( $k, $allowed_attributes ) ){
						$attr_string .= sprintf( ' %s="%s"', sanitize_key( is_int( $k ) ? $v : $k ), esc_attr( $v ) );
					}
				}
				if ( isset( $href ) ) {
					$script_html = sprintf( '<script type="text/javascript" src="%s" %s></script>', $href, $attr_string );
					echo apply_filters( "ngx_http_concat_script_loader_tag",  $script_html, $href, $attr_string );
				}
				if ( isset( $js_array['extras']['after'] ) ) {
					foreach ( $js_array['extras']['after'] as $inline_after ) {
						echo $inline_after;
					}
				}
			}
		}

		do_action( 'js_concat_did_items', $javascripts );
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
		return isset( $this->old_scripts->$key );
	}

	function __unset( $key ) {
		unset( $this->old_scripts->$key );
	}

	function &__get( $key ) {
		return $this->old_scripts->$key;
	}

	function __set( $key, $value ) {
		$this->old_scripts->$key = $value;
	}
}

function js_concat_init() {
	global $wp_scripts;

	$wp_scripts = new WPcom_JS_Concat( $wp_scripts );
	$wp_scripts->allow_gzip_compression = ALLOW_GZIP_COMPRESSION;
}

add_action( 'init', 'js_concat_init' );
