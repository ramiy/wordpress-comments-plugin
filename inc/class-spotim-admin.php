<?php

class SpotIM_Admin {
    private static $options;

    public static function launch( $options ) {
        self::$options = $options;

        add_action( 'admin_menu', array( __CLASS__, 'create_admin_menu' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function create_admin_menu() {
        $menu_icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxNiAxNyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48dGl0bGU+Y2hhdCBjb3B5PC90aXRsZT48cGF0aCBkPSJNLjc0IDE1LjkxbC42MzQtMi42MTVjLjA1Ni0uMjMuMDEtLjQ4LS4xMy0uNzA3Qy0xLjg0NyA3LjU3OCAxLjE0NSAxLjAzIDYuNjY1LjExYzUuMzg2LS44OTYgMTAuMDkgMy43OTMgOS4yMzMgOS40MjItLjc4NiA1LjE2Ny02LjE5NCA4LjMxLTEwLjk3IDYuMjYtLjI1LS4xMS0uNTE4LS4xMS0uNzM0LS4wMDNMMS45NCAxNi45MWMtLjY1LjMyMi0xLjM3My0uMjc3LTEuMi0xem0yLjE5LTQuMzFjLjIzLjM3My4zLjguMjA2IDEuMjA1TDIuNjEzIDE1bDEuODU3LS45NGMuMzczLS4xOS44Mi0uMTk1IDEuMjMtLjAxNiAzLjU3IDEuNTU4IDcuNjM1LS44MjIgOC4yMjUtNC43Ny42MzQtNC4yNDUtMi44MjctNy44ODItNi45My03LjE5QzIuODI1IDIuNzk1LjYzIDcuODAyIDIuOTMgMTEuNnoiIGZpbGw9IiNGRkYiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvc3ZnPg==';

        add_menu_page(
            __( 'Spot.IM Settings', 'wp-spotim' ),
            __( 'Spot.IM', 'wp-spotim' ),
            'manage_options',
            self::$options->slug,
            array( __CLASS__, 'admin_page_callback' ),
            $menu_icon
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
            __( 'Commenting Options', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'general_settings_section_header' ),
            self::$options->slug
        );

        add_settings_field(
            'enable_comments_replacement',
            __( 'Enable Spot.IM comments', 'wp-spotim' ),
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
            __( 'Enable Spot.IM on pages', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'yes_no_fields' ),
            self::$options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => self::$options->slug,
                'value' => self::$options->get( 'enable_comments_on_page' )
            )
        );

        $translated_spot_id_description = __('Find your Spot ID at the Spot.IM\'s %1$sAdmin Dashboard%2$s under Integrations section.%3$s Don\'t have an account? %4$sCreate%5$s one for free!' , 'wp-spotim');

        $parsed_translated_spot_id_description = sprintf( $translated_spot_id_description,
            '<a href="https://www.spot.im/login" target="_blank">',
            '</a>',
            '<br />',
            '<a href="http://www.spot.im/" target="_blank">',
            '</a>'
        );

        add_settings_field(
            'spot_id',
            __( 'Your Spot ID', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'text_field' ),
            self::$options->slug,
            'general_settings_section',
            array(
                'id' => 'spot_id',
                'page' => self::$options->slug,
                'description' => $parsed_translated_spot_id_description,
                'value' => self::$options->get( 'spot_id' )
            )
        );
    }

    public static function validate_options( $input ) {
        $options = self::$options->get_meta_data();

        foreach ( $input as $key => $value ) {
            switch( $key ) {
                case 'enable_comments_replacement':
                case 'enable_comments_on_page':
                    $options[$key] = intval( $value );
                    break;
                case 'spot_id':
                    $options[$key] = sanitize_text_field( $value );
                    break;
            }
        }

        return $options;
    }

    public static function admin_page_callback() {
        self::$options->require_template( 'admin-template.php' );
    }
}
