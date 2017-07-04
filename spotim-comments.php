<?php
/**
 * Plugin Name:         Spot.IM Comments
 * Plugin URI:          https://wordpress.org/plugins/spotim-comments/
 * Description:         Real-time comments widget turns your site into its own content-circulating ecosystem.
 * Version:             4.1.0
 * Author:              Spot.IM
 * Author URI:          https://github.com/SpotIM
 * License:             GPLv2
 * License URI:         license.txt
 * Text Domain:         spotim-comments
 * GitHub Plugin URI:   git@github.com:SpotIM/wordpress-comments-plugin.git
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load plugin files
require_once( 'inc/helpers/class-spotim-form.php' );
require_once( 'inc/helpers/class-spotim-message.php' );
require_once( 'inc/helpers/class-spotim-comment.php' );
require_once( 'inc/class-spotim-i18n.php' );
require_once( 'inc/class-spotim-endpoint.php' );
require_once( 'inc/class-spotim-export.php' );
require_once( 'inc/class-spotim-import.php' );
require_once( 'inc/class-spotim-options.php' );
require_once( 'inc/class-spotim-settings-fields.php' );
require_once( 'inc/class-spotim-metabox.php' );
require_once( 'inc/class-spotim-admin.php' );
require_once( 'inc/class-spotim-frontend.php' );
require_once( 'inc/class-spotim-feed.php' );
require_once( 'inc/class-spotim-cron.php' );
require_once( 'inc/spotim-shortcodes.php' );
require_once( 'inc/spotim-widgets.php' );

/**
 * WP_SpotIM
 *
 * A general class for Spot.IM comments for WordPress.
 *
 * @since 1.0.2
 */
class WP_SpotIM {

    /**
     * Instance
     *
     * @since 1.0.2
     *
     * @access private
     * @static
     *
     * @var WP_SpotIM
     */
    private static $instance;

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 1.0.2
     *
     * @access protected
     */
    protected function __construct() {

        // Activation/Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'flush_rewrite_rules' ) );
        register_deactivation_hook( __FILE__, array( $this, 'flush_rewrite_rules' ) );

        // Get the Options
        $this->options = SpotIM_Options::get_instance();

        // Run the plugin
        new SpotIM_i18n();
        new SpotIM_Cron( $this->options );
        new SpotIM_Feed();

        if ( is_admin() ) {

            // Admin Page
            new SpotIM_Admin( $this->options );

        } else {

            // Frontend code: embed script, comments template, comments count.
            new SpotIM_Frontend( $this->options );

        }

    }

    /**
     * Get Instance
     *
     * @since 2.0.0
     *
     * @access public
     * @static
     *
     * @return WP_SpotIM
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    /**
     * Flush rewrite rules
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

}

/**
 * Spotim Instance
 *
 * @since 1.0
 *
 * @return WP_SpotIM
 */
function spotim_instance() {
    return WP_SpotIM::get_instance();
}

add_action( 'after_setup_theme', 'spotim_instance' );
