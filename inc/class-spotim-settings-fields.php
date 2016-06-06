<?php

class SpotIM_Settings_Fields {
    public function __construct( $options ) {
        $this->options = $options;
    }

    public function register_settings() {
        register_setting(
            $this->options->option_group,
            $this->options->slug,
            array( $this->options, 'validate' )
        );
    }

    public function general_settings_section_header() {
        echo '<p>' . esc_html__( 'These are some basic settings for Spot.IM.', 'wp-spotim' ) . '</p>';
    }

    public function import_settings_section_header() {
        echo '<p>' . esc_html__( 'Some explanation about Spot.IM import to WordPress.', 'wp-spotim' ) . '</p>';
    }

    public function register_general_section() {
        add_settings_section(
            'general_settings_section',
            __( 'Commenting Options', 'wp-spotim' ),
            array( $this, 'general_settings_section_header' ),
            $this->options->slug
        );

        add_settings_field(
            'enable_comments_replacement',
            __( 'Enable Spot.IM comments', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_replacement',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_comments_replacement' )
            )
        );

        add_settings_field(
            'enable_comments_on_page',
            __( 'Enable Spot.IM on pages', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_comments_on_page' )
            )
        );

        $translated_spot_id_description = __('Find your Spot ID at the Spot.IM\'s %1$sAdmin Dashboard%2$s under Integrations section.%3$s Don\'t have an account? %4$sCreate%5$s one for free!' , 'wp-spotim');

        $parsed_translated_spot_id_description = sprintf( $translated_spot_id_description,
            '<a href="https://admin.spot.im/login" target="_blank">',
            '</a>',
            '<br />',
            '<a href="http://www.spot.im/" target="_blank">',
            '</a>'
        );

        add_settings_field(
            'spot_id',
            __( 'Your Spot ID', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'general_settings_section',
            array(
                'id' => 'spot_id',
                'page' => $this->options->slug,
                'description' => $parsed_translated_spot_id_description,
                'value' => $this->options->get( 'spot_id' )
            )
        );
    }

    public function register_import_section() {
        add_settings_section(
            'import_settings_section',
            __( 'Import Options', 'wp-spotim' ),
            array( $this, 'import_settings_section_header' ),
            $this->options->slug
        );

        add_settings_field(
            'spot_id',
            __( 'Your Token', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'import_token',
                'page' => $this->options->slug,
                'description' => 'some description about where to get the api key.',
                'value' => $this->options->get( 'import_token' )
            )
        );

        add_settings_field(
            'import_button',
            '',
            array( 'SpotIM_Form_Helper', 'button' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'import_button',
                'text' => __( 'Import', 'wp-spotim' ),
                'description' => ''
            )
        );
    }
}
