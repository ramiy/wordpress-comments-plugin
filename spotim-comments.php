<?php
/**
 *
 * Official Spot.IM WP Plugin
 *
 * Plugin Name:         Spot.IM Comments
 * Plugin URI:          https://github.com/SpotIM/wordpress-comments-plugin
 * Description:         Real-time comments widget turns your site into its own content-circulating ecosystem. Implement an innovative conversation UI and dynamic newsfeed to spur user engagement, growth, and retention.
 * Version:             3.1.0
 * Author:              Spot.IM (@Spot_IM)
 * Author URI:          https://github.com/SpotIM
 * License:             GPLv2
 * License URI:         license.txt
 * Text Domain:         wp-spotim
 * GitHub Plugin URI:   git@github.com:SpotIM/wordpress-comments-plugin.git
 *
 */
defined('ABSPATH') || exit;

define('SPOTIM_EXPORT', true);

function init_spotim_once_per_plugin()
{
}

require_once dirname(__FILE__).'/spotim/spotim-comments.php';

register_activation_hook( __FILE__, 'spotim_register' );
add_action( 'activated_plugin', 'spotim_activation_redirect' );

/// Do not move this function!!!
function spotim_activation_redirect( $plugin ) {
	if( $plugin == plugin_basename( __FILE__ ) ) {
		WP_SpotIM::get_instance()->redirect_to_settings();
	}
}

