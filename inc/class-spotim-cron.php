<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SpotIM_Cron
 *
 * Plugin auto import cron job.
 *
 * @since 4.0.0
 */
class SpotIM_Cron {

    /**
     * Options
     *
     * @since 4.0.0
     *
     * @access private
     * @static
     *
     * @var SpotIM_Options
     */
    private static $options;

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 4.0.0
     *
     * @param SpotIM_Options $options Plugin options.
     *
     * @access public
     */
    public function __construct( $options ) {
        self::$options = $options;
        add_action( 'wp', array( $this, 'auto_import_cron_job' ) );
    }

    /**
     * Auto import cron job
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function auto_import_cron_job() {

        // Auto import interval
        $interval = self::$options->get( 'auto_import' );

        // Check if auto import enabled
        if ( ! in_array( $interval, array( 'hourly', 'twicedaily', 'daily' ) ) )
            return;

        // Schedule cron job event, if not scheduled yet
        if ( ! wp_next_scheduled( 'spotim_scheduled_import', array() ) ) {
            wp_schedule_event( time(), $interval, 'spotim_scheduled_import' );
        }

        // Run cron job hook - import data
        add_action( 'spotim_scheduled_import', array( $this, 'run_import' ) );

    }

    /**
     * Run import
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function run_import() {

        $spot_id           = sanitize_text_field( self::$options->get( 'spotim_spot_id' ) );
        $import_token      = sanitize_text_field( self::$options->get( 'spotim_import_token' ) );
        $page_number       = empty( self::$options->get( 'spotim_page_number' ) ) ? absint( self::$options->get( 'spotim_page_number' ) ) : 0;
        $posts_per_request = empty( self::$options->get( 'spotim_posts_per_request' ) ) ? absint( self::$options->get( 'spotim_posts_per_request' ) ) : 0;

        if ( empty( $spot_id ) )
            return;

        if ( empty( $import_token ) )
            return;

        $import = new SpotIM_Import( self::$options );
        $import->start( $spot_id, $import_token, $page_number, $posts_per_request );

    }

}