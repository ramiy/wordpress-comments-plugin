<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SpotIM_Settings_Fields
 *
 * Plugin settings fields.
 *
 * @since 2.0.0
 */
class SpotIM_Settings_Fields {

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @param SpotIM_Options $options Plugin options.
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Register Settings
     *
     * Register admin settings for the plugin.
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            $this->options->option_group,
            $this->options->slug,
            array( $this->options, 'validate' )
        );
    }

    /**
     * General Settings Section Header
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function general_settings_section_header() {
        echo '<p>' . esc_html__( 'Basic settings to integrate your Spot.IM account.', 'spotim-comments' ) . '</p>';
    }

    /**
     * Display Settings Section Header
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function display_settings_section_header() {
        echo '<p>' . esc_html__( 'These are some display settings on your theme.', 'spotim-comments' ) . '</p>';
    }

    /**
     * Import Settings Section Header
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function import_settings_section_header() {
        echo '<p>' . esc_html__( 'Import your comments from Spot.IM to WordPress.', 'spotim-comments' ) . '</p>';
    }

    /**
     * Register General Section
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function register_general_section() {
        add_settings_section(
            'general_settings_section',
            esc_html__( 'General Options', 'spotim-comments' ),
            array( $this, 'general_settings_section_header' ),
            $this->options->slug
        );

        $translated_spot_id_description = sprintf(
		    __('Find your Spot ID at the <a href="%s" target="_blank">Spot.IM\'s Admin Dashboard</a> under "Features" section.' , 'spotim-comments'),
            'https://admin.spot.im/login'
        ) . ' ' . sprintf(
            __('Don\'t have an account? <a href="%s" target="_blank">Create</a> one for free!' , 'spotim-comments'),
            'https://admin.spot.im/login'
        );

        add_settings_field(
            'spot_id',
            esc_html__( 'Spot ID', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'general_settings_section',
            array(
                'id' => 'spot_id',
                'page' => $this->options->slug,
                'description' => $translated_spot_id_description,
                'value' => $this->options->get( 'spot_id' )
            )
        );

    }

    /**
     * Register Display Section
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function register_display_section() {
        add_settings_section(
            'display_settings_section',
            esc_html__( 'Display Options', 'spotim-comments' ),
            array( $this, 'display_settings_section_header' ),
            $this->options->slug
        );

        add_settings_field(
            'enable_comments_replacement',
            esc_html__( 'Enable Spot.IM comments', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'display_settings_section',
            array(
                'id' => 'enable_comments_replacement',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_comments_replacement' )
            )
        );

        add_settings_field(
            'enable_questions_replacement',
            esc_html__( 'Enable Spot.IM questions', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'display_settings_section',
            array(
                'id' => 'enable_questions_replacement',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_questions_replacement' )
            )
        );

        add_settings_field(
            'enable_comments_on_page',
            esc_html__( 'Enable Spot.IM on pages', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'display_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_comments_on_page' )
            )
        );

    }

    /**
     * Register Import Section
     *
     * @since 2.0.0
     *
     * @access public
     *
     * @return void
     */
    public function register_import_section() {
        add_settings_section(
            'import_settings_section',
            esc_html__( 'Import Options', 'spotim-comments' ),
            array( $this, 'import_settings_section_header' ),
            $this->options->slug
        );

        add_settings_field(
            'import_token',
            esc_html__( 'Your Token', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'import_token',
                'page' => $this->options->slug,
                'description' => esc_html__( 'Don\'t have a token? please send us an email to support@spot.im and get one.', 'spotim-comments' ),
                'value' => $this->options->get( 'import_token' )
            )
        );

        add_settings_field(
            'posts_per_request',
            esc_html__( 'Posts Per Request', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'posts_per_request',
                'page' => $this->options->slug,
                'description' => esc_html__( 'Amount of posts to retrieve in each request, depending on your server\'s strength.', 'spotim-comments' ),
                'value' => $this->options->get( 'posts_per_request' )
            )
        );

        add_settings_field(
            'auto_import',
            esc_html__( 'Auto Import', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'radio_fields' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'auto_import',
                'page' => $this->options->slug,
                'description' => esc_html__( 'Enable Auto-Import and how ofter should it reoccur.', 'spotim-comments' ),
                'fields' => array(
                    '0' => esc_html__( 'No', 'spotim-comments' ),
                    'hourly' => esc_html__( 'Hourly', 'spotim-comments' ),
                    'twicedaily' => esc_html__( 'Twice Daily', 'spotim-comments' ),
                    'daily' => esc_html__( 'Daily', 'spotim-comments' ),
                ),
                'value' => $this->options->get( 'auto_import' )
            )
        );

        add_settings_field(
            'import_button',
            esc_html__( 'Manual Import', 'spotim-comments' ),
            array( 'SpotIM_Form_Helper', 'import_button' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'import_button' => array(
                    'id' => 'import_button',
                    'text' => esc_html__( 'Import Now!', 'spotim-comments' )
                ),
                'cancel_import_link' => array(
                    'id' => 'cancel_import_link',
                    'text' => esc_html__( 'Cancel', 'spotim-comments' )
                )
            )
        );

    }
}
