<?php
defined('ABSPATH') || exit;

defined('SPOTIM_EXPORT') || define('SPOTIM_EXPORT', false);

function LOG_HERE($msg = false)
{
	$stack = debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
	if (count($stack) < 2)
		return;

	$when = date("Y-m-d H:i:s");
	$userMsg = '';
	if ($msg) {
		$userMsg = " - ";
		$userMsg .= is_string($msg) ? $msg : print_r($msg,1);
	}
	$file = $stack[0]['file'];
	$line = $stack[0]['line'];
	$function = $stack[1]['function'];
	$file = isset($file) ? substr($file, strlen(__DIR__)) : 'UNKNOWN-FILE';
	@file_put_contents(__DIR__.'/log', "$when - $file:$line($function)$userMsg\n", FILE_APPEND);
}

define('SPOTIM_ABSDIR', plugin_dir_path( dirname( __FILE__ ) ).'/');
define('SPOTIM_BASEDIR', SPOTIM_ABSDIR.'spotim/');
define('SPOT_IM_REST_API', 'https://open-api.spot.im/v1/wordpress/');

require_once( 'inc/helpers/class-spotim-form.php' );
require_once( 'inc/helpers/class-spotim-message.php' );
require_once( 'inc/helpers/class-spotim-comment.php' );

require_once( 'inc/class-spotim-import.php' );
require_once( 'inc/class-spotim-options.php' );
require_once( 'inc/class-spotim-settings-fields.php' );
require_once( 'inc/class-spotim-admin.php' );
require_once( 'inc/class-spotim-frontend.php' );
require_once( 'inc/class-spotim-posttypes.php' );
require_once( 'inc/class-spotim-export.php' );

class WP_SpotIM {
	private static $instance;
	private $options, $export;

	protected function __construct()
	{
		$this->options = SpotIM_Options::get_instance();

		if (is_admin()) {

			// Launch Admin Page
			SpotIM_Admin::launch($this->options);
		} else {

			// Launch frontend code: embed script, comments template, comments count.
			SpotIM_Frontend::launch($this->options);
		}
		if (SPOTIM_EXPORT) {
			$this->export = new SpotIM_Export();
			$this->export->add_endpoint();
		}
	}

	public function redirect_to_settings()
	{
		exit( wp_redirect( admin_url( 'admin.php?page=wp-spotim-settings' ) ) );
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
/*
	public function spot_additional_attributes()
	{
		$additional_attributes = '';
		if (SPOTIM_EXPORT && $this->export->get_export_access_token())
		{
			global $post;
			$additional_attributes = ' data-wp-import-endpoint="'.
						$this->export->get_export_endpoint()."&post_id={$post->ID}\"";
		}
		echo $additional_attributes;
	}
*/
}

function spotim_instance() {
	return WP_SpotIM::get_instance();
}
add_action( 'init', 'spotim_instance', 88 );

//////////////////////////////////////////////////////////////
function spotim_register($redirect = false) {
//	LOG_HERE('Entered '.__FUNCTION__);

	if (function_exists('init_spotim_once_per_plugin')) {
		init_spotim_once_per_plugin();
	}

	require_once 'inc/class-spotim-register.php';
	$registrar = new SpotIM_Register();
	return $registrar->register($redirect);
}

function spotim_register_by_upgrader($upgrader_object, $data) {
	$plugin_destination_name = plugin_basename( dirname(__DIR__) );
	LOG_HERE('Entered '.__FUNCTION__." with plugin_destination_name: $plugin_destination_name\ndata: ".print_r($data,1)."\nupgrader_object: ".print_r($upgrader_object,1));
	if (!empty($data) && $data['type'] === 'plugin' &&
		!empty($upgrader_object->result) &&
		array_key_exists('destination_name', $upgrader_object->result) &&
		$plugin_destination_name == $upgrader_object->result['destination_name'])
	{
		spotim_register(true);
	}
}
add_action( 'upgrader_process_complete', 'spotim_register_by_upgrader', 10, 2 );
