<?php

class SpotIM_Admin {
    private static $options;

    public static function launch( $options ) {
        self::$options = $options;

        add_action( 'admin_menu', array( __CLASS__, 'create_admin_menu' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function create_admin_menu() {
        add_menu_page(
            __( 'Spot.IM Settings', self::$options->lang_slug ),
            __( 'Spot.IM', self::$options->lang_slug ),
            'manage_options',
            self::$options->slug,
            array( __CLASS__, 'admin_page_callback' )
        );
    }

    public static function register_settings() {
        register_setting(
            self::$options->option_group,
            self::$options->slug,
            array( __CLASS__, 'validate_options' )
        );

        add_settings_section(
            'general_settings_section',
            __( 'Commenting Options', self::$options->lang_slug ),
            array( 'SpotIM_Settings_Fields', 'general_settings_section_header' ),
            self::$options->slug
        );

        add_settings_field(
            'enable_comments_replacement',
            __( 'Enable Spot.IM comments', self::$options->lang_slug ),
            array( 'SpotIM_Settings_Fields', 'yes_no_fields' ),
            self::$options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_replacement',
                'page' => self::$options->slug,
                'value' => self::$options->get( 'enable_comments_replacement' )
            )
        );

        add_settings_field(
            'enable_comments_on_page',
            __( 'Enable Spot.IM on pages', self::$options->lang_slug ),
            array( 'SpotIM_Settings_Fields', 'yes_no_fields' ),
            self::$options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => self::$options->slug,
                'value' => self::$options->get( 'enable_comments_on_page' )
            )
        );

        add_settings_field(
            'spot_id',
            __( 'Your Spot ID', self::$options->lang_slug ),
            array( 'SpotIM_Settings_Fields', 'text_field' ),
            self::$options->slug,
            'general_settings_section',
            array(
                'id' => 'spot_id',
                'page' => self::$options->slug,
                'description' => "Find your Spot ID at the Spot.IM's <a href='https://www.spot.im/login' target='_blank'>Admin Dashboard</a> under Integrations section.<br> Don't have an account? <a href='http://www.spot.im/'' target='_blank'>Create</a> one for free!",
                'value' => self::$options->get( 'spot_id' )
            )
        );
    }

    public static function validate_options($input) {
        $options = self::$options->get_meta_tags();

        // @todo some data validation/sanitization should go here
        $output = apply_filters( 'spotim_validate_options', $input, $options );

        // merge with current settings
        $output = array_merge( $options, $output );

        return $output;
    }

    public static function admin_page_callback() {
        require_once( self::$options->templates_path . 'admin-template.php' );
    }
}
