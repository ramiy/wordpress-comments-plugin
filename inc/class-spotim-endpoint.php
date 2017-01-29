<?php

/**
 * Created by PhpStorm.
 * User: Ronny
 * Date: 30/08/2016
 * Time: 10:37
 */
abstract class SpotIM_Endpoint
{
	protected $options, $endpoint;

	public function __construct()
	{
		$this->options = SpotIM_Options::get_instance();
	}

	public function add_endpoint() {

		global $wp_rewrite;
		$wp_rewrite->add_endpoint($this->endpoint, EP_ROOT);
//		$wp_rewrite->add_endpoint($this->endpoint.'_unittest', EP_ROOT);		// DEBUG only
		$wp_rewrite->flush_rules();
		add_action('template_redirect', array($this, 'do_endpoint'));
		add_filter('query_vars', array($this, 'add_query_vars'));
		add_filter('wp_headers', array($this, 'access_control_allow_origin'), 91, 1);
	}

	abstract public function do_endpoint();

	protected function get_data()
	{
		$name = get_query_var('name');
		if ($name != $this->endpoint) {
			return false;
		}
		try {
			$post_data = json_decode( $this->get_raw_data() );
		}
		catch (Exception $e) {
			return false;
		}
		return $post_data;
	}

	public function add_query_vars($vars)
	{
		return $vars;
	}

	public function access_control_allow_origin( $headers ) {

		$headers['Access-Control-Allow-Origin'] = get_http_origin(); // Can't use wildcard origin, instead use the requesting origin
		$headers['Access-Control-Allow-Credentials'] = 'true';
		$headers['Access-Control-Allow-Methods'] = 'POST';
		$headers['Access-Control-Allow-Headers'] = 'Content-Type';

		return $headers;
	}

	// Copied from class WP_REST_Server
	private function get_raw_data() {
		global $HTTP_RAW_POST_DATA;

		/*
		 * A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
		 * but we can do it ourself.
		 */
		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
	}
}