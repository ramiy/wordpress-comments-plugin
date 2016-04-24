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

class WP_SpotIM {
    private static $_instance;

    protected function __construct() {
        $this->admin = new SpotIM_Admin;

        if (!is_admin()) {

            // Launch embed code
            SpotIM_Frontend::setup();

            // Import comments via JSON
            if ( isset( $_GET['json-comments'] ) &&
                ( isset( $_GET['p'] ) && ! empty( $_GET['p'] ) ) ) {

                $post_ids = (array) $_GET['p'];
                SpotIM_Export::generate_json_by_post($post_ids);
            }
        }
    }

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
}

function spotim_instance() {
    return WP_SpotIM::instance();
}

add_action( 'plugins_loaded', 'spotim_instance' );