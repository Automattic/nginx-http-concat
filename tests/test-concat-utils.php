<?php

class WPCOM_Concat_Utils__Is_External_Url__TestCase extends WP_UnitTestCase {
	function get_test_data() {
		return array(
			// External
			'single_site__external_url' => array(
				'https://fonts.googleapis.com/path/to/file.css',
				'https://example.com',
				false,
			),
			
			'subdir_site__external_url' => array(
				'https://fonts.googleapis.com/path/to/file.css',
				'https://example.com/mysite',
				false,
			),

			// Interal: Full URL
			'single_site__internal_url' => array(
				'https://example.com/wp-content/plugins/plugin/style.css',
				'https://example.com',
				true,
			),

			'single_site_trailingslash__internal_url' => array(
				'https://example.com/wp-content/plugins/plugin/style.css',
				'https://example.com/',
				true,
			),


			'subdir_site__internal_subdir_url' => array(
				'https://example.com/mysite/wp-content/plugins/plugin/style.css',
				'https://example.com/mysite',
				true,
			),

			'subdir_site__internal_root_url' => array(
				'https://example.com/wp-content/plugins/plugin/style.css',
				'https://example.com/mysite',
				false,
			),

			// Internal: absolute (i.e. no host, /wp-content/path/to/file.css)
			'single_site__internal_absolute_url' => array(
				'/wp-content/plugins/plugin/style.css',
				'https://example.com',
				true,
			),

			'subdir_site__internal_absolute_url' => array(
				'/wp-content/plugins/plugin/style.css',
				'https://example.com/mysite',
				true,
			),
		);
	}

	/**
	 * @dataProvider get_test_data
	 */
	function test__function( $test_url, $site_url, $expected ) {
		$actual = WPCOM_Concat_Utils::is_internal_url( $test_url, $site_url );

		$this->assertSame( $expected, $actual );
	}
}

class WPCOM_Concat_Utils__Replace_Relative_Url__TestCase extends WP_UnitTestCase {
	function get_test_data() {
		return array(
			// Relative URL with double quotes.
			'relative_url_double_quotes' => array(
				'src:url("social-logos.eot?51b607ee5b5cb2a0e4517176475a424c");',
				'src:url(/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
			),
			// Relative URL with double quotes subdir.
			'relative_url_double_quotes_subdir' => array(
				'src:url("social-logos.eot?51b607ee5b5cb2a0e4517176475a424c");',
				'src:url(/mysite/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
				'/mysite',
			),
			// Relative URL with single quotes.
			'relative_url_single_quotes' => array(
				'src:url(\'social-logos.eot?51b607ee5b5cb2a0e4517176475a424c\');',
				'src:url(/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);'
			),
			// Relative URL with single quotes subdir.
			'relative_url_single_quotes_subdir' => array(
				'src:url(\'social-logos.eot?51b607ee5b5cb2a0e4517176475a424c\');',
				'src:url(/mysite/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
				'/mysite',
			),
			// Relative URL no quotation marks.
			'relative_url_no_quotes' => array(
				'src:url(social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
				'src:url(/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
			),
			// Relative URL no quotation marks subdir.
			'relative_url_no_quotes_subdir' => array(
				'src:url(social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
				'src:url(/mysite/social-logos.eot?51b607ee5b5cb2a0e4517176475a424c);',
				'/mysite',
			),
			// Absolute URL double quotes.
			'absolute_url_double_quotes' => array(
				'src:url("https://fonts.googleapis.com/path/to/file.css");',
				'src:url("https://fonts.googleapis.com/path/to/file.css");',
			),
			// Absolute URL single quotes.
			'absolute_url_single_quotes' => array(
				'src:url(\'https://fonts.googleapis.com/path/to/file.css\');',
				'src:url(\'https://fonts.googleapis.com/path/to/file.css\');',
			),
			// Absolute URL no quotes.
			'absolute_url_no_quotes' => array(
				'src:url(https://fonts.googleapis.com/path/to/file.css);',
				'src:url(https://fonts.googleapis.com/path/to/file.css);',
			),
			// Relative URL containing hashtag in double quotes.
			'relative_url_with_hashtag_double_quotes' => array(
				'src:url("social-logos.eot?#iefix");',
				'src:url(/social-logos.eot?#iefix);',
			),
			// Relative URL containing hashtag in double quotes subdir.
			'relative_url_with_hashtag_double_quotes_subdir' => array(
				'src:url("social-logos.eot?#iefix");',
				'src:url(/mysite/social-logos.eot?#iefix);',
				'/mysite',
			),
			// Relative URL contaiing hashtag in single quotes.
			'relative_url_with_hashtag_single_quotes' => array(
				'src:url(\'social-logos.eot?#iefix\');',
				'src:url(/social-logos.eot?#iefix);',
			),
			// Relative URL contaiing hashtag in single quotes subdir.
			'relative_url_with_hashtag_single_quotes_subdir' => array(
				'src:url(\'social-logos.eot?#iefix\');',
				'src:url(/mysite/social-logos.eot?#iefix);',
				'/mysite',
			),
			// Relative URL containig hashtag w/o quotes.
			'relative_url_with_hashtag_no_quotes' => array(
				'src:url(social-logos.eot?#iefix);',
				'src:url(/social-logos.eot?#iefix);',
			),
			// Relative URL containig hashtag w/o quotes subdir.
			'relative_url_with_hashtag_no_quotes_subdir' => array(
				'src:url(social-logos.eot?#iefix);',
				'src:url(/mysite/social-logos.eot?#iefix);',
				'/mysite',
			),
			// Relative URL starting with hashtag double quotes.
			'relative_url_starting_with_hashtag_double_quotes' => array(
				'src:url("#social-logos.eot")',
				'src:url("#social-logos.eot")',
			),
			// Relative URL starting with hashtag single quotes.
			'relative_url_starting_with_hashtag_single_quotes' => array(
				'src:url(\'#social-logos.eot\')',
				'src:url(\'#social-logos.eot\')',
			),
			// Relative URL starting with hashtag no quotes.
			'relative_url_starting_with_hashtag_no_quotes' => array(
				'src:url(#social-logos.eot)',
				'src:url(#social-logos.eot)',
			),
			// data: URL double quotes.
			'data_url_double_quotes' => array(
				'src:url(url("data:application/x-font-woff;charset=utf-8;base64,d09GRk9UVE8AAEZAAAoAAAAAfBAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABDRkYgAAAA9AAAQsUAAHZfa1y5A0ZGVE0AAEO8AAAAGQAAABx4Dt9ZT1MvMgAAQ9gAAABKAAAAYEC7Yj5jbWFwAABEJAAAAIUAAAG6pEWcoGhlYWQAAESsAAAALwAAADYHEbeJaGhlYQAARNwAAAAdAAAAJAOvAd5obXR4AABE/AAAADgAAABeCDEE521heHAAAEU0AAAABgAAAAYALVAAbmFtZQAARTwAAADrAAAB5koHYmpwb3N0AABGKAAAABYAAAAg/8MAGnicrZ13mJXFFfDn3XbvVnb37i596SC9d8uLDQyKFXtD7F0RYzSGay9LDCpqjB1REaPR2CXCFbGB2ACR3otLWdje5zu/M++9QGK+fH98Dzyz804vZ86cOtczKSnG87zsKTdMvmrStX2vveGKG6YYL8l45qTaFqZ2rFc7Lqn2hOTaVinTs7zibW36ZyUXd3vVZqUUZ5jWp+fbkpJEJCv04mW102p7pbZLat2inTG57ZJuzmtn+rSb2jLf9KfJsGlhikx709X0NoPMSOObsWaCmWjON5PN1eYmc5uZZu43fzYzzTNmlnndvG0+NPPNF+Zbs8ysNpvMTlNmqk2Tl+Jlevlea6+j18Pr5w2Vf2O8k6Zef9XxAwcM5M+gAQPcn+BrkPsz2P0Z6v4Mc3+Guz8j9c9AV2+gqzf")',
				'src:url(url("data:application/x-font-woff;charset=utf-8;base64,d09GRk9UVE8AAEZAAAoAAAAAfBAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABDRkYgAAAA9AAAQsUAAHZfa1y5A0ZGVE0AAEO8AAAAGQAAABx4Dt9ZT1MvMgAAQ9gAAABKAAAAYEC7Yj5jbWFwAABEJAAAAIUAAAG6pEWcoGhlYWQAAESsAAAALwAAADYHEbeJaGhlYQAARNwAAAAdAAAAJAOvAd5obXR4AABE/AAAADgAAABeCDEE521heHAAAEU0AAAABgAAAAYALVAAbmFtZQAARTwAAADrAAAB5koHYmpwb3N0AABGKAAAABYAAAAg/8MAGnicrZ13mJXFFfDn3XbvVnb37i596SC9d8uLDQyKFXtD7F0RYzSGay9LDCpqjB1REaPR2CXCFbGB2ACR3otLWdje5zu/M++9QGK+fH98Dzyz804vZ86cOtczKSnG87zsKTdMvmrStX2vveGKG6YYL8l45qTaFqZ2rFc7Lqn2hOTaVinTs7zibW36ZyUXd3vVZqUUZ5jWp+fbkpJEJCv04mW102p7pbZLat2inTG57ZJuzmtn+rSb2jLf9KfJsGlhikx709X0NoPMSOObsWaCmWjON5PN1eYmc5uZZu43fzYzzTNmlnndvG0+NPPNF+Zbs8ysNpvMTlNmqk2Tl+Jlevlea6+j18Pr5w2Vf2O8k6Zef9XxAwcM5M+gAQPcn+BrkPsz2P0Z6v4Mc3+Guz8j9c9AV2+gqzf")',
			),
			'data_url_single_quotes' => array(
				'src:url(url(\'data:application/x-font-woff;charset=utf-8;base64,d09GRk9UVE8AAEZAAAoAAAAAfBAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABDRkYgAAAA9AAAQsUAAHZfa1y5A0ZGVE0AAEO8AAAAGQAAABx4Dt9ZT1MvMgAAQ9gAAABKAAAAYEC7Yj5jbWFwAABEJAAAAIUAAAG6pEWcoGhlYWQAAESsAAAALwAAADYHEbeJaGhlYQAARNwAAAAdAAAAJAOvAd5obXR4AABE/AAAADgAAABeCDEE521heHAAAEU0AAAABgAAAAYALVAAbmFtZQAARTwAAADrAAAB5koHYmpwb3N0AABGKAAAABYAAAAg/8MAGnicrZ13mJXFFfDn3XbvVnb37i596SC9d8uLDQyKFXtD7F0RYzSGay9LDCpqjB1REaPR2CXCFbGB2ACR3otLWdje5zu/M++9QGK+fH98Dzyz804vZ86cOtczKSnG87zsKTdMvmrStX2vveGKG6YYL8l45qTaFqZ2rFc7Lqn2hOTaVinTs7zibW36ZyUXd3vVZqUUZ5jWp+fbkpJEJCv04mW102p7pbZLat2inTG57ZJuzmtn+rSb2jLf9KfJsGlhikx709X0NoPMSOObsWaCmWjON5PN1eYmc5uZZu43fzYzzTNmlnndvG0+NPPNF+Zbs8ysNpvMTlNmqk2Tl+Jlevlea6+j18Pr5w2Vf2O8k6Zef9XxAwcM5M+gAQPcn+BrkPsz2P0Z6v4Mc3+Guz8j9c9AV2+gqzf\')',
				'src:url(url(\'data:application/x-font-woff;charset=utf-8;base64,d09GRk9UVE8AAEZAAAoAAAAAfBAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABDRkYgAAAA9AAAQsUAAHZfa1y5A0ZGVE0AAEO8AAAAGQAAABx4Dt9ZT1MvMgAAQ9gAAABKAAAAYEC7Yj5jbWFwAABEJAAAAIUAAAG6pEWcoGhlYWQAAESsAAAALwAAADYHEbeJaGhlYQAARNwAAAAdAAAAJAOvAd5obXR4AABE/AAAADgAAABeCDEE521heHAAAEU0AAAABgAAAAYALVAAbmFtZQAARTwAAADrAAAB5koHYmpwb3N0AABGKAAAABYAAAAg/8MAGnicrZ13mJXFFfDn3XbvVnb37i596SC9d8uLDQyKFXtD7F0RYzSGay9LDCpqjB1REaPR2CXCFbGB2ACR3otLWdje5zu/M++9QGK+fH98Dzyz804vZ86cOtczKSnG87zsKTdMvmrStX2vveGKG6YYL8l45qTaFqZ2rFc7Lqn2hOTaVinTs7zibW36ZyUXd3vVZqUUZ5jWp+fbkpJEJCv04mW102p7pbZLat2inTG57ZJuzmtn+rSb2jLf9KfJsGlhikx709X0NoPMSOObsWaCmWjON5PN1eYmc5uZZu43fzYzzTNmlnndvG0+NPPNF+Zbs8ysNpvMTlNmqk2Tl+Jlevlea6+j18Pr5w2Vf2O8k6Zef9XxAwcM5M+gAQPcn+BrkPsz2P0Z6v4Mc3+Guz8j9c9AV2+gqzf\')',
			),
			// Absolute URL non-secure double quotes.
			'absolute_url_http_double_quotes' => array(
				'src:url("http://fonts.googleapis.com/path/to/file.css")',
				'src:url("http://fonts.googleapis.com/path/to/file.css")',
			),
			// Absolute URL non-secure single quotes.
			'absolute_url_http_single_quotes' => array(
				'src:url(\'http://fonts.googleapis.com/path/to/file.css\')',
				'src:url(\'http://fonts.googleapis.com/path/to/file.css\')',
			),
			// Absolute URL non-secure no quotes.
			'absolute_url_http_no_quotes' => array(
				'src:url(http://fonts.googleapis.com/path/to/file.css)',
				'src:url(http://fonts.googleapis.com/path/to/file.css)',
			),
			// Absolute URL containing hashtag
			'absolute_url_containig_hashtag' => array(
				'src:url(http://fonts.googleapis.com/path/to/file.css#iefix)',
				'src:url(http://fonts.googleapis.com/path/to/file.css#iefix)',
			),
		);
	}

	/**
	 * @dataProvider get_test_data
	 */
	function test__function( $test_string, $expected, $dirpath = '/' ) {
		$actual = WPCOM_Concat_Utils::relative_path_replace( $test_string, $dirpath );
		$this->assertSame( $expected, $actual );
	}
}

class WPCOM_Concat_Utils__Strip_Utf8_Bom__TestCase extends WP_UnitTestCase {
	const BOM = "\xEF\xBB\xBF";

	function get_test_data() {
		return array(
			// Nothing to strip.
			'empty_string' => array(
				'',
				'',
				false,
			),
			'no_bom' => array(
				'*{margin:0;padding:0}',
				'*{margin:0;padding:0}',
				false,
			),
			// The buffer is shorter than a BOM. The code must not read too far.
			'single_byte' => array(
				'a',
				'a',
				false,
			),
			'two_bytes' => array(
				'ab',
				'ab',
				false,
			),
			// An incomplete BOM is not a BOM.
			'partial_bom' => array(
				"\xEF\xBB",
				"\xEF\xBB",
				false,
			),
			'partial_bom_then_text' => array(
				"\xEF\xBBz",
				"\xEF\xBBz",
				false,
			),
			// The reported bug. A BOM is before the first rule of a file.
			// https://github.com/Automattic/nginx-http-concat/issues/49
			'bom_then_rule' => array(
				self::BOM . '*{margin:0;padding:0}',
				'*{margin:0;padding:0}',
				true,
			),
			// The @charset test is at offset 0. A BOM breaks that test.
			'bom_then_charset' => array(
				self::BOM . '@charset "UTF-8";',
				'@charset "UTF-8";',
				true,
			),
			'bom_only' => array(
				self::BOM,
				'',
				true,
			),
			// Some build tools add a BOM to a file that already has one.
			'repeated_boms' => array(
				self::BOM . self::BOM . self::BOM . '.a{color:red}',
				'.a{color:red}',
				true,
			),
			// A BOM gives the encoding only at offset 0. In other positions it is content.
			'bom_mid_stream_is_left_alone' => array(
				'.a{color:red}' . self::BOM . '.b{color:blue}',
				'.a{color:red}' . self::BOM . '.b{color:blue}',
				false,
			),
			'bom_at_end_is_left_alone' => array(
				'.a{color:red}' . self::BOM,
				'.a{color:red}' . self::BOM,
				false,
			),
			// UTF-16 and UTF-32 bytes are not valid in the bundle. Keep the BOM,
			// because UTF-8 is the wrong encoding for that content.
			'utf16_le_bom_untouched' => array(
				"\xFF\xFE.a{color:red}",
				"\xFF\xFE.a{color:red}",
				false,
			),
			'utf16_be_bom_untouched' => array(
				"\xFE\xFF.a{color:red}",
				"\xFE\xFF.a{color:red}",
				false,
			),
			// The first character has more than one byte, but it is not a BOM.
			'leading_non_bom_multibyte_char' => array(
				"\xE2\x86\x92.a{color:red}",
				"\xE2\x86\x92.a{color:red}",
				false,
			),
		);
	}

	/**
	 * @dataProvider get_test_data
	 */
	function test__strip_utf8_bom( $test_string, $expected, $expected_has_bom ) {
		$this->assertSame( $expected, WPCOM_Concat_Utils::strip_utf8_bom( $test_string ) );
	}

	/**
	 * @dataProvider get_test_data
	 */
	function test__has_utf8_bom( $test_string, $expected, $expected_has_bom ) {
		$this->assertSame( $expected_has_bom, WPCOM_Concat_Utils::has_utf8_bom( $test_string ) );
	}

	/**
	 * A safe buffer has no BOM. Therefore a second call must make no change.
	 *
	 * @dataProvider get_test_data
	 */
	function test__strip_utf8_bom_is_idempotent( $test_string, $expected, $expected_has_bom ) {
		$once = WPCOM_Concat_Utils::strip_utf8_bom( $test_string );

		$this->assertSame( $once, WPCOM_Concat_Utils::strip_utf8_bom( $once ) );
		$this->assertFalse( WPCOM_Concat_Utils::has_utf8_bom( $once ) );
	}

	/**
	 * The stream must have no BOM after offset 0. The position of the file
	 * with the BOM must not change this result.
	 */
	function test__concatenating_stripped_buffers_leaves_no_stray_bom() {
		$files = array(
			'body{color:red}',
			self::BOM . '*{margin:0;padding:0}',
			'.c{color:blue}',
		);

		$output = '';
		foreach ( $files as $buf ) {
			$output .= WPCOM_Concat_Utils::strip_utf8_bom( $buf );
		}

		$this->assertSame( 'body{color:red}*{margin:0;padding:0}.c{color:blue}', $output );
		$this->assertStringNotContainsString( self::BOM, $output );
	}
}
