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
require_once 'inc/class-spotim-generate-json-conversation.php';
require_once 'inc/class-spotim-settings-fields.php';
require_once 'inc/class-spotim-admin.php';
require_once 'inc/class-spotim-frontend.php';

class WP_SpotIM {
    private static $instance;

    protected function __construct() {
        $this->admin = new SpotIM_Admin();
        $this->frontend = new SpotIM_Frontend( $this->admin );

        if ( ! is_admin() ) {
            // Launch embed code
            $this->frontend->launch();

            // Import comments via JSON
            // if ( isset( $_GET['json-comments'] ) &&
            //     ( isset( $_GET['p'] ) && ! empty( $_GET['p'] ) ) ) {

            //     $post_ids = (array) $_GET['p'];
            //     SpotIM_Export::generate_json_by_post($post_ids);
            // }
        }
    }

    public static function run() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}

function spotim_instance() {
    return WP_SpotIM::run();
}

add_action( 'plugins_loaded', 'spotim_instance' );
