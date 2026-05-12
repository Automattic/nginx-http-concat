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

	public function test_source_less_handle_without_inline_emits_no_output() {
		$alias  = 'vip-concat-no-inline-alias';
		$first  = 'vip-concat-no-inline-first';
		$second = 'vip-concat-no-inline-second';

		$this->enqueue_script( $first, 'wp-util.js' );
		$this->enqueue_script( $second, 'comment-reply.js' );
		wp_register_script( $alias, false, array( $first, $second ), null );
		wp_enqueue_script( $alias );

		add_filter( 'js_do_concat', '__return_false' );

		$output = $this->get_scripts_output();

		$first_position  = strpos( $output, 'id="vip-concat-no-inline-first-js"' );
		$second_position = strpos( $output, 'id="vip-concat-no-inline-second-js"' );

		$this->assertNotFalse( $first_position, 'First dependency must be output.' );
		$this->assertNotFalse( $second_position, 'Second dependency must be output.' );
		$this->assertGreaterThan( $first_position, $second_position );

		$this->assertStringNotContainsString( 'id="vip-concat-no-inline-alias-js"', $output );
		$this->assertStringNotContainsString( 'id="vip-concat-no-inline-alias-js-before"', $output );
		$this->assertStringNotContainsString( 'id="vip-concat-no-inline-alias-js-after"', $output );
	}

	public function test_do_items_preserves_source_less_handle_order_with_before_inline_position() {
		$group  = 'vip-concat-source-less-before';
		$first  = 'vip-concat-before-first';
		$second = 'vip-concat-before-second';

		$this->enqueue_script( $first, 'wp-util.js' );
		$this->enqueue_script( $second, 'comment-reply.js' );
		wp_register_script( $group, false, array( $first, $second ), null );
		wp_add_inline_script( $group, 'window.vipConcatSourceLessBefore = true;', 'before' );
		wp_enqueue_script( $group );

		add_filter( 'js_do_concat', '__return_false' );

		$output = $this->get_scripts_output();

		$first_position = strpos( $output, 'id="vip-concat-before-first-js"' );
		$second_position = strpos( $output, 'id="vip-concat-before-second-js"' );
		$group_position = strpos( $output, 'id="vip-concat-source-less-before-js-before"' );

		$this->assertNotFalse( $first_position );
		$this->assertNotFalse( $second_position );
		$this->assertNotFalse( $group_position );
		$this->assertGreaterThan( $first_position, $second_position );
		$this->assertGreaterThan( $second_position, $group_position );
		$this->assertStringNotContainsString( 'id="vip-concat-source-less-before-js-after"', $output );
	}

	public function test_do_items_handles_multiple_consecutive_source_less_handles() {
		$alias_one = 'vip-concat-multi-alias-one';
		$alias_two = 'vip-concat-multi-alias-two';
		$first     = 'vip-concat-multi-first';
		$second    = 'vip-concat-multi-second';

		// Both aliases share the same two deps. all_deps() will deduplicate them,
		// producing to_do = [first, second, alias_one, alias_two].
		$this->enqueue_script( $first, 'wp-util.js' );
		$this->enqueue_script( $second, 'comment-reply.js' );
		wp_register_script( $alias_one, false, array( $first, $second ), null );
		wp_register_script( $alias_two, false, array( $first, $second ), null );
		wp_add_inline_script( $alias_one, 'window.vipConcatAliasOne = true;' );
		wp_add_inline_script( $alias_two, 'window.vipConcatAliasTwo = true;' );
		wp_enqueue_script( $alias_one );
		wp_enqueue_script( $alias_two );

		add_filter( 'js_do_concat', '__return_false' );

		$output = $this->get_scripts_output();

		$first_position     = strpos( $output, 'id="vip-concat-multi-first-js"' );
		$second_position    = strpos( $output, 'id="vip-concat-multi-second-js"' );
		$alias_one_position = strpos( $output, 'id="vip-concat-multi-alias-one-js-after"' );
		$alias_two_position = strpos( $output, 'id="vip-concat-multi-alias-two-js-after"' );

		$this->assertNotFalse( $first_position );
		$this->assertNotFalse( $second_position );
		$this->assertNotFalse( $alias_one_position );
		$this->assertNotFalse( $alias_two_position );
		// Both deps appear before either alias's inline output.
		$this->assertGreaterThan( $first_position, $alias_one_position );
		$this->assertGreaterThan( $second_position, $alias_one_position );
		// The two aliases maintain their enqueue order relative to each other.
		$this->assertGreaterThan( $alias_one_position, $alias_two_position );
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
