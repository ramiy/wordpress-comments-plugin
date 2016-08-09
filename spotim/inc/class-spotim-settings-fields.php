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
        echo '<p>' . esc_html__( 'Import your comments from Spot.IM to WordPress.', 'wp-spotim' ) . '</p>';
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
            sprintf(__( 'Enable Spot.IM on %s', 'wp-spotim' ), __('pages', 'wp-spotim')),
            array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
            $this->options->slug,
            'general_settings_section',
            array(
                'id' => 'enable_comments_on_page',
                'page' => $this->options->slug,
                'value' => $this->options->get( 'enable_comments_on_page' )
            )
        );

        for ($i = SpotIM_posttypes::get_instance()->count()-1; $i >= 0; --$i) {
            $typeObj = SpotIM_posttypes::get_instance()->get($i);
            add_settings_field(
                $typeObj->option,
                sprintf(__( 'Enable Spot.IM on %s', 'wp-spotim' ), $typeObj->name),
                array( 'SpotIM_Form_Helper', 'yes_no_fields' ),
                $this->options->slug,
                'general_settings_section',
                array(
                    'id' => $typeObj->option,
                    'page' => $this->options->slug,
                    'value' => $this->options->get( $typeObj->option ),
                    'description' => __( 'The comments will be visible only if they are defined as open.', 'wp-spotim' ),
                )
            );
        }

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
                'value' => $this->options->get( 'spot_id' ),
                'readonly' => ($this->options->get( 'spot_id' ) != '')
            )
        );
        if ($this->options->get( 'plugin_secret' ) == '') {
            add_settings_field(
                'plugin_secret',
                __('Your Plugin Secret', 'wp-spotim'),
                array('SpotIM_Form_Helper', 'text_field'),
                $this->options->slug,
                'general_settings_section',
                array(
                    'id' => 'plugin_secret',
                    'page' => $this->options->slug,
//                    'description' => $parsed_translated_spot_id_description,
                    'value' => $this->options->get( 'plugin_secret' )
                )
            );
        }
    }

    public function register_import_section() {
        add_settings_section(
            'import_settings_section',
            __( 'Import Options', 'wp-spotim' ),
            array( $this, 'import_settings_section_header' ),
            $this->options->slug
        );

        add_settings_field(
            'import_token',
            __( 'Your Token', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'import_token',
                'page' => $this->options->slug,
                'description' => 'Don\'t have a token? please send us an email to support@spot.im and get one.',
                'value' => $this->options->get( 'import_token' )
            )
        );

        add_settings_field(
            'posts_per_request',
            __( 'Posts Per Request', 'wp-spotim' ),
            array( 'SpotIM_Form_Helper', 'text_field' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'id' => 'posts_per_request',
                'page' => $this->options->slug,
                'description' => 'Amount of posts to retrieve in each request, depending on your server\'s strength.',
                'value' => $this->options->get( 'posts_per_request' )
            )
        );

        add_settings_field(
            'import_button',
            '',
            array( 'SpotIM_Form_Helper', 'import_button' ),
            $this->options->slug,
            'import_settings_section',
            array(
                'import_button' => array(
                    'id' => 'import_button',
                    'text' => __( 'Import', 'wp-spotim' )
                ),
                'cancel_import_link' => array(
                    'id' => 'cancel_import_link',
                    'text' => __( 'Cancel', 'wp-spotim' )
                )
            )
        );
    }

    private function get_custom_post_types()
    {
        $args = array(
            'public'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'show_ui'  => true,
            '_builtin' => false
        );

        return get_post_types( $args, 'objects' );
    }
}
