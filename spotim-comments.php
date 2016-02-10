<?php
/**
 * @package Spot
 * @version 0.1
 */

/*
  Plugin Name: Spot.IM
  Plugin URI: http://spot.im
  Description: Description for wp-spotim should be here
  Author: SpotIM
  Version: 1.10.6
  Author URI: http://maorchasen.com/
 */

require_once 'inc/class-spotim-export.php';
require_once 'inc/class-spotim-export-comment-authors.php';
require_once 'inc/class-spotim-generate-json-conversation.php';
require_once 'inc/class-spotim-admin.php';
require_once 'inc/class-spotim-util.php';
require_once 'inc/class-spotim-frontend.php';
require_once 'inc/abstract-class-spotim-api-base.php';
// require_once 'inc/class-spotim-api-dispatcher.php';

class WP_SpotIM {

    private static $_instance;

    const AUTH_OPTION = 'spotim_auth';

    protected function __construct() {



	$this->admin = new SpotIM_Admin;



	// $this->api = new SpotIM_API_Dispatcher;



	// setup front-end



	if (!is_admin()) {



	    SpotIM_Frontend::setup();



	    $get = $_GET;



	    if ((isset($get['p']) && $get['p'] != '') && isset($get['json-comments'])) {



		$post_ids = (array) $get['p'];



		SpotIM_Export::generate_json_by_post($post_ids);



	    }



	}



    }







    /**



     * @return WP_SpotIM



     */

    public static function instance() {

	if (is_null(self::$_instance))

	    self::$_instance = new self;

	return self::$_instance;



    }



    public static function activation_hook() {

	// create a spot via API

	// self::instance()->api->initiate_setup();

    }

}



function spotim_instance() {



    return WP_SpotIM::instance();

}

add_action('plugins_loaded', 'spotim_instance');

register_activation_hook(__FILE__, array('WP_SpotIM', 'activation_hook'));