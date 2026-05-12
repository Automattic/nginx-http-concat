<?php

class WPcom_JS_Concat__Source_Less_Order__TestCase extends WP_UnitTestCase {
	private $old_wp_scripts;
	private $created_files = array();

	public function set_up() {
		parent::set_up();

		global $wp_scripts;

		$this->old_wp_scripts = $wp_scripts;
		$wp_scripts           = new WPcom_JS_Concat( new WP_Scripts() );

		$wp_scripts->allow_gzip_compression = false;
		$wp_scripts->base_url               = site_url();
	}

	public function tear_down() {
		global $wp_scripts;

		$wp_scripts = $this->old_wp_scripts;

		remove_filter( 'js_do_concat', '__return_false' );

		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		parent::tear_down();
	}

	public function test_do_items_preserves_source_less_handle_order_when_concat_disabled() {
		$group  = 'vip-concat-source-less-group';
		$first  = 'vip-concat-disabled-first';
		$second = 'vip-concat-disabled-second';

		$this->enqueue_script( $first, 'wp-util.js' );
		$this->enqueue_script( $second, 'comment-reply.js' );
		wp_register_script( $group, false, array( $first, $second ), null );
		wp_add_inline_script( $group, 'window.vipConcatSourceLessGroup = true;' );
		wp_enqueue_script( $group );

		add_filter( 'js_do_concat', '__return_false' );

		$output = $this->get_scripts_output();

		$first_position  = strpos( $output, 'id="vip-concat-disabled-first-js"' );
		$second_position = strpos( $output, 'id="vip-concat-disabled-second-js"' );
		$group_position  = strpos( $output, 'id="vip-concat-source-less-group-js-after"' );

		$this->assertNotFalse( $first_position );
		$this->assertNotFalse( $second_position );
		$this->assertNotFalse( $group_position );
		$this->assertGreaterThan( $first_position, $second_position );
		$this->assertGreaterThan( $second_position, $group_position );
	}

	public function test_do_items_preserves_source_less_handle_order_when_concat_enabled() {
		$group  = 'vip-concat-source-less-group-enabled';
		$first  = 'vip-concat-enabled-first';
		$second = 'vip-concat-enabled-second';
		$first_file  = 'vip-concat-enabled-first.js';
		$second_file = 'vip-concat-enabled-second.js';

		$this->create_concat_test_script( $first_file );
		$this->create_concat_test_script( $second_file );

		if ( 0 !== strpos( WPCOM_Concat_Utils::realpath( '/' . $first_file, site_url() ), ABSPATH ) ) {
			$this->markTestSkipped( 'This WordPress test install resolves ABSPATH through a symlink, so test scripts are not concat eligible.' );
		}

		wp_enqueue_script( $first, '/' . $first_file, array(), null );
		wp_enqueue_script( $second, '/' . $second_file, array(), null );
		wp_register_script( $group, false, array( $first, $second ), null );
		wp_add_inline_script( $group, 'window.vipConcatSourceLessGroupEnabled = true;' );
		wp_enqueue_script( $group );

		$output = $this->get_scripts_output();

		$concat_position = strpos( $output, '/_static/??/' . $first_file . ',/' . $second_file . '?m=' );
		$group_position  = strpos( $output, 'id="vip-concat-source-less-group-enabled-js-after"' );

		$this->assertNotFalse( $concat_position );
		$this->assertNotFalse( $group_position );
		$this->assertGreaterThan( $concat_position, $group_position );
		$this->assertStringNotContainsString( 'id="vip-concat-enabled-first-js"', $output );
		$this->assertStringNotContainsString( 'id="vip-concat-enabled-second-js"', $output );
	}

	private function enqueue_script( $handle, $filename, $deps = array() ) {
		$src = '/' . WPINC . '/js/' . $filename;

		$this->assertFileExists( ABSPATH . ltrim( $src, '/' ) );

		wp_enqueue_script( $handle, $src, $deps, null );
	}

	private function create_concat_test_script( $filename ) {
		$file = ABSPATH . $filename;

		file_put_contents( $file, 'window.' . str_replace( '-', '', $filename ) . ' = true;' );

		$this->created_files[] = $file;
	}

	private function get_scripts_output() {
		ob_start();
		wp_scripts()->do_items();

		return ob_get_clean();
	}
}
