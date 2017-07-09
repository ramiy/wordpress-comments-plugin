<?php
/**
 * Tests SpotIM main class
 *
 * @author Spot.IM
 */
class WP_SpotIM_TestCase extends WP_UnitTestCase {

	/**
	 * Holds the plugin base class
	 *
	 * @return void
	 */
	protected $plugin;

	/**
	 * Custom action prefix for test custom triggered actions
	 * @var string
	 */
	protected $action_prefix = 'wp_stream_test_';

	/**
	 * PHP unit setup function
	 *
	 * @return void
	 */
	function setUp() {
		parent::setUp();
	}

	/**
	 * Make sure the plugin is initialized with its global variable
	 *
	 * @return void
	 */
	public function test_php_version() {
		$this->assertTrue( true );
	}
}