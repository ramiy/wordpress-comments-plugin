<?php

/**
 *
 * Official Spot.IM WP Plugin
 *
 * Plugin Name:         Spot.IM
 * Plugin URI:          https://www.spot.im
 * Description:         Official Spot.IM WP Plugin
 * Version:             2.0.0
 * Author:              Spot.IM (@Spot_IM)
 * Author URI:          https://github.com/SpotIM
 * License:             GPLv2
 * License URI:         license.txt
 * Text Domain:         wp-spotim
 * GitHub Plugin URI:   git@github.com:SpotIM/WP-Plugin.git
 *
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
            SpotIM_Admin::launch( $this->options );
        } else {

            // Launch frontend code: embed script, comments template, comments count.
            SpotIM_Frontend::launch( $this->options );
        }
    }

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}

function spotim_instance() {
    return WP_SpotIM::get_instance();
}

add_action( 'plugins_loaded', 'spotim_instance' );

?>
