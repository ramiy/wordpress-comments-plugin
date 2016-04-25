<?php

class SpotIM_Admin {
    protected $screens = array();
    protected $options;
    public $slug = 'wp-spotim-settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function create_admin_menu() {
        $this->screens['main'] = add_menu_page(
            __( 'Spot.IM Settings', 'wp-spotim' ),
            __( 'Spot.IM', 'wp-spotim' ),
            'manage_options',
            $this->slug,
            array( $this, 'admin_page_callback' )
        );
    }

    public function register_settings() {
        $this->options = $this->get_options();

        // If no options exist, create them.
        if ( ! get_option( $this->slug ) ) {
            update_option( $this->slug, apply_filters( 'spotim_default_options', array(
                'enable_comments_replacement' => true,
                'spot_id' => 'sp_foo',
            )));
        }

        register_setting( 'wp-spotim-options', $this->slug, array( $this, 'validate_options' ) );

        add_settings_section(
            'general_settings_section',
            __( 'Commenting Options', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'general_settings_section_header' ),
            $this->slug
        );

        add_settings_field(
            'enable_comments_replacement',
            __( 'Enable Spot.IM comments?', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'yesno_field' ),
            $this->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_replacement',
                'page' => $this->slug,
                'value' => 1
            )
        );

        add_settings_field(
            'enable_comments_on_page',
            __( 'Enable Spot.IM on pages?', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'yesno_field' ),
            $this->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => $this->slug,
            )
        );

        add_settings_field(
            'spot_id',
            __( 'Your Spot ID', 'wp-spotim' ),
            array( 'SpotIM_Settings_Fields', 'text_field' ),
            $this->slug,
            'general_settings_section',
            array(
                'id' => 'spot_id',
                'page' => $this->slug,
                'desc' => 'Find your Spot\'s ID at the <a href="https://www.spot.im/login" target="_blank">Spot management dashboard</a>.<br> Don\'t have an account? <a href="http://www.spot.im/" target="_blank">Create one</a> for free!'
            )
        );
    }

    public function get_option( $key = '', $default_value = false ) {
        $settings = $this->get_options();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : $default_value;
    }

    public function get_options() {

        // Allow other plugins to get spotim's options.
        if ( isset( $this->options ) && is_array( $this->options ) &&
            ! empty( $this->options ) ) {
            return $this->options;
        }

        return apply_filters( 'spotim_options', get_option( $this->slug, array() ) );
    }

    public function validate_options($input) {
        $options = $this->options; // CTX,L1504

        // @todo some data validation/sanitization should go here
        $output = apply_filters( 'spotim_validate_options', $input, $options );

        // merge with current settings
        $output = array_merge( $options, $output );

        return $output;
    }

    public function admin_page_callback() {
        ?>
        <div class="wrap">
            <div id="icon-themes" class="icon32"></div>
            <h2 class="spotim-page-title"><?php _e( 'Spot.IM Settings', 'wp-spotim' ); ?></h2>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'wp-spotim-options' );
                    do_settings_sections( $this->slug );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
