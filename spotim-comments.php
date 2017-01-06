<?php

/**
 *
 * Official Spot.IM WP Plugin
 *
 * Plugin Name:         Spot.IM Comments
 * Plugin URI:          https://github.com/SpotIM/wordpress-comments-plugin
 * Description:         Real-time comments widget turns your site into its own content-circulating ecosystem. Implement an innovative conversation UI and dynamic newsfeed to spur user engagement, growth, and retention.
 * Version:             3.0.0
 * Author:              Spot.IM (@Spot_IM)
 * Author URI:          https://github.com/SpotIM
 * License:             GPLv2
 * License URI:         license.txt
 * Text Domain:         spotim-comments
 * GitHub Plugin URI:   git@github.com:SpotIM/wordpress-comments-plugin.git
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( 'inc/helpers/class-spotim-form.php' );
require_once( 'inc/helpers/class-spotim-message.php' );
require_once( 'inc/helpers/class-spotim-comment.php' );

require_once( 'inc/class-spotim-import.php' );
require_once( 'inc/class-spotim-options.php' );
require_once( 'inc/class-spotim-settings-fields.php' );
require_once( 'inc/class-spotim-admin.php' );
require_once( 'inc/class-spotim-frontend.php' );

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

add_action( 'after_setup_theme', 'spotim_instance' );
