<?php

class WPCOM_Concat_Utils {
	public static function is_internal_url( $test_url, $site_url ) {
		$test_url_parsed = parse_url( $test_url );
		$site_url_parsed = parse_url( $site_url );

		if ( isset( $test_url_parsed['host'] )
			&& $test_url_parsed['host'] !== $site_url_parsed['host'] ) {
			return false;
		}

		if ( isset( $site_url_parsed['path'] )
			&& 0 !== strpos( $test_url_parsed['path'], $site_url_parsed['path'] )
			&& isset( $test_url_parsed['host'] ) //and if the URL of enqueued style is not relative
		) {
			return false;
		}	

		return true;
	}
	public static function realpath( $url ) {
		$url_parsed = parse_url( $url );
		if ( true === is_multisite() && false == constant( 'SUBDOMAIN_INSTALL' ) ) {
			$blog_details = get_blog_details();
			if ( '/' !== $blog_details->path ) {
				//In case of subdir multisite, we need to remove the blog's path from the style's path in order to be able to find the file
				$realpath = realpath( ABSPATH . str_replace( $blog_details->path, '', $url_parsed['path'] ) );
			} else {
				$realpath = realpath( ABSPATH . $url_parsed['path'] );
			}
		} else {
			$realpath = realpath( ABSPATH . $url_parsed['path'] );
		}
		return $realpath;
	}
}
