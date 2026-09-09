<?php
class WPCOM_Concat_Utils {
	// Maximum group size, anything over that will be split into multiple groups
	protected static int $concat_max = 150;

	// UTF-8 byte order mark.
	const UTF8_BOM = "\xEF\xBB\xBF";

	public static function get_concat_max() {
		return self::$concat_max;
	}

	public static function is_internal_url( $test_url, $site_url ) {
		$test_url_parsed = parse_url( is_string( $test_url ) ? $test_url : '' );
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

	public static function realpath( $url, $site_url ) {
		$url_path = parse_url( $url, PHP_URL_PATH );
		$site_url_path = parse_url( $site_url, PHP_URL_PATH );
		// To avoid partial matches; subdir install at `/wp` would match `/wp-includes`
		$site_url_path = is_null( $site_url_path ) ? '/' : trailingslashit( $site_url_path );

		// If this is a subdirectory site, we need to strip off the subdir from the URL.
		// In a multisite install, the subdir is virtual and therefore not needed in the path.
		// In a single-site subdir install, the subdir is included in the ABSPATH and therefore ends up duplicated.
		if ( $site_url_path && '/' !== $site_url_path
			&& 0 === strpos( $url_path, $site_url_path ) ) {
			$url_path_without_subdir = preg_replace( '#^' . $site_url_path . '#', '', $url_path, 1 );
			return realpath( ABSPATH . $url_path_without_subdir );
		}

		return realpath( ABSPATH . $url_path );
	}

	/**
	 * Tells if a buffer starts with a UTF-8 byte order mark.
	 *
	 * @param string $buf File contents.
	 * @return bool
	 */
	public static function has_utf8_bom( $buf ) {
		return 0 === strncmp( $buf, self::UTF8_BOM, strlen( self::UTF8_BOM ) );
	}

	/**
	 * Removes UTF-8 byte order marks from the start of a buffer.
	 *
	 * A BOM gives the encoding only at offset 0. In a bundle, the BOM of a later
	 * file becomes a U+FEFF character in the middle of the stream. CSS accepts
	 * that character in a selector. The browser then adds it to the next
	 * selector, and ignores that rule.
	 *
	 * The function removes more than one BOM, because some build tools add a BOM to
	 * a file that already has one. It keeps a UTF-16 or UTF-32 BOM. Those bytes are
	 * not valid in a UTF-8 bundle.
	 *
	 * @see https://github.com/Automattic/nginx-http-concat/issues/49
	 *
	 * @param string $buf File contents.
	 * @return string The buffer without a BOM at the start.
	 */
	public static function strip_utf8_bom( $buf ) {
		$bom_len = strlen( self::UTF8_BOM );
		$len     = strlen( $buf );
		$offset  = 0;

		// Count the BOMs in one pass. A file of only BOMs then needs one copy.
		while ( $offset + $bom_len <= $len
			&& 0 === substr_compare( $buf, self::UTF8_BOM, $offset, $bom_len ) ) {
			$offset += $bom_len;
		}

		return 0 === $offset ? $buf : substr( $buf, $offset );
	}

	public static function relative_path_replace( $buf, $dirpath ) {
		// url(relative/path/to/file) -> url(/absolute/and/not/relative/path/to/file)
		$buf = preg_replace(
			'/(:?\s*url\s*\()\s*(?:\'|")?\s*([^\/\'"\s\)](?:(?<!data:|http:|https:|[\(\'"]#|%23).)*)[\'"\s]*\)/isU',
			'$1' . ( $dirpath == '/' ? '/' : $dirpath . '/' ) . '$2)',
			$buf
		);

		return $buf;
	}
}
