<?php

class SpotIM_Admin {
    private static $options;

    public static function launch( $options ) {
        self::$options = $options;

        add_action( 'admin_menu', array( __CLASS__, 'create_admin_menu' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
        add_action( 'wp_ajax_start_import', array( __CLASS__, 'import_callback' ) );
        add_action( 'wp_ajax_cancel_import', array( __CLASS__, 'cancel_import_callback' ) );
    }

    public static function admin_assets( $hook ) {
        if ( 'toplevel_page_wp-spotim-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'admin_stylesheet', self::$options->require_stylesheet( 'admin.css', true ) );
        wp_enqueue_script( 'admin_javascript', self::$options->require_javascript( 'admin.js', true ), array( 'jquery' ) );

        wp_localize_script( 'admin_javascript', 'spotimVariables', array(
            'pageNumber' => self::$options->get('page_number'),
            'errorMessage' => __( 'Oops something got wrong. Please try again or send us an email.', 'wp-spotim' ),
            'cancelImportMessage' => __( 'Cancel importing...', 'wp-spotim' )
        ) );
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
        $settings_fields = new SpotIM_Settings_Fields( self::$options );

        $settings_fields->register_settings();
        $settings_fields->register_general_section();
        $settings_fields->register_import_section();
    }

    public static function admin_page_callback() {
        self::$options->require_template( 'admin-template.php' );
    }

    public static function import_callback() {
        $import = new SpotIM_Import( self::$options );

        // check for spot id
        if ( ! isset( $_POST['spotim_spot_id'] ) || empty( $_POST['spotim_spot_id'] ) ) {
            $import->response( array(
                'status' => 'error',
                'message' => __( 'Your Spot Id is missing.', 'wp-spotim' )
            ) );

        // check for import token
        } else if ( ! isset( $_POST['spotim_import_token'] ) || empty( $_POST['spotim_import_token'] ) ) {
            $import->response( array(
                'status' => 'error',
                'message' => __( 'Your Token is missing.', 'wp-spotim' )
            ) );

        //  else start the comments importing process
        } else {
            $spot_id = sanitize_text_field( $_POST['spotim_spot_id'] );
            $import_token = sanitize_text_field( $_POST['spotim_import_token'] );
            $page_number = isset( $_POST['spotim_page_number'] ) ? absint( $_POST['spotim_page_number'] ) : 0;

            if ( isset( $_POST['spotim_posts_per_request'] ) ) {
                $posts_per_request = absint( $_POST['spotim_posts_per_request'] );
                $posts_per_request = 0 === $posts_per_request ? 1 : $posts_per_request;
            } else {
                $posts_per_request = 1;
            }


            $import->start( $spot_id, $import_token, $page_number, $posts_per_request );
        }
    }

    public static function cancel_import_callback() {
        $import = new SpotIM_Import( self::$options );
        $page_number = isset( $_POST['spotim_page_number'] ) ? absint( $_POST['spotim_page_number'] ) : 0;

        self::$options->update( 'page_number', $page_number );

        $import->response( array(
            'status' => 'cancel'
        ) );
    }
}
