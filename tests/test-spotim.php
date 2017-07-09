<?php

class Test_WP_SpotIM extends WP_SpotIM_TestCase {
	public function test_get_instance() {
		$instance = spotim_instance();
		$this->assertInstanceOf( 'WP_SpotIM', $instance );
	}
}