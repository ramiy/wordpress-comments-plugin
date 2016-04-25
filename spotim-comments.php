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

require_once 'inc/class-spotim-options.php';
require_once 'inc/class-spotim-settings-fields.php';
require_once 'inc/class-spotim-admin.php';
require_once 'inc/class-spotim-frontend.php';

class WP_SpotIM {
    private static $instance;

    protected function __construct() {
        $this->options = SpotIM_Options::get_instance();

        if ( is_admin() ) {
            // Launch Admin Page
            $this->admin = new SpotIM_Admin( $this->options );
        } else {
            // Launch embed code
            SpotIM_Frontend::launch( $this->options );
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
