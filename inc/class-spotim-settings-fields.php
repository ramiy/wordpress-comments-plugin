<?php

class SpotIM_Settings_Fields {
    public static function general_settings_section_header() {
        $lang_slug = SpotIM_Options::get_instance()->lang_slug;
        $title = __( 'These are some basic settings for SpotIM.', $lang_slug );

        echo "<p>$title</p>";
    }

    private static function set_name( $args ) {
        if ( ! isset( $args['name'] ) ) {
            $args['name'] = sprintf(
                '%s[%s]', esc_attr( $args['page'] ), esc_attr( $args['id'] )
            );
        }

        return $args;
    }

    public static function yes_no_fields( $args ) {
        $lang_slug = SpotIM_Options::get_instance()->lang_slug;
        $args = self::set_name( $args );
        $radio_template = '<label class="description">' .
            '<input type="radio" name="%s" value="%d" %s /> %s' .
        '&nbsp;&nbsp;&nbsp;</label>';

        // Backward compatability condition
        if ( ! isset( $args['value'] ) || ! $args['value'] ) {
            $args['value'] = '0';
        }

        // Yes template
        $template = sprintf($radio_template,
            esc_attr( $args['name'] ), // Input's name.
            1, // Input's value.
            checked( $args['value'], '1', 0 ), // If input checked or not.
            __( 'Yes', $lang_slug ) // Translated text.
        );

        // No template
        $template .= sprintf($radio_template,
            esc_attr( $args['name'] ), // Input's name.
            0, // Input's value.
            checked( $args['value'], '0', 0 ), // If input checked or not.
            __( 'No', $lang_slug ) // Translated text.
        );

        // Description template
        if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
            $description_template = sprintf( '<p class="description">%s</p>', $args['description'] );
            $template .= $description_template;
        }

        echo $template;
    }

    public static function text_field( $args ) {
        $args = self::set_name( $args );
        $args['value'] = sanitize_text_field( $args['value'] );
        $text_template = '<input name="%s" type="text" value="%s" />';

        // Text input template
        $template = sprintf($text_template,
            esc_attr( $args['name'] ), // Input's name.
            esc_attr( $args['value'] ) // Input's value.
        );

        // Description template
        if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
            $description_template = sprintf( '<p class="description">%s</p>', $args['description'] );
            $template .= $description_template;
        }

        echo $template;
    }
}