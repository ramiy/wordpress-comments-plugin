<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SpotIM Recirculation Shortcode
 *
 * Plugin frontend.
 *
 * @since 4.0.0
 */
function spotim_recirculation_shortcode() {

	$options = SpotIM_Options::get_instance();
	$spot_id = $options->get( 'spot_id' );

	include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/recirculation-template.php' );
}
add_shortcode( 'spotim_recirculation', 'spotim_recirculation_shortcode' );