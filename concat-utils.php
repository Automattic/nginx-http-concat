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
			&& 0 !== strpos( $test_url_parsed['path'], $site_url_parsed['path'] ) ) {
			return false;
		}	

		return true;
	}
}
