<?php

class SpotIM_Settings_Fields {
    public static function general_settings_section_header() {
        $title = __( 'These are some basic settings for Spot.IM.', 'wp-spotim' );

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
        $args = self::set_name( $args );
        $radio_template = '<label class="description">' .
            '<input type="radio" name="%s" value="%d" %s /> %s' .
        '&nbsp;&nbsp;&nbsp;</label>';
        $yes_value = 1;
        $no_value = 0;

        // Backward compatability condition
        if ( ! isset( $args['value'] ) || false === $args['value'] ) {
            $args['value'] = $no_value;
        } else if ( true === $args['value'] ) {
            $args['value'] = $yes_value;
        }

        // Yes template
        $escaped_template = sprintf($radio_template,
            esc_attr( $args['name'] ), // Input's name.
            $yes_value, // Input's value.
            checked( $args['value'], $yes_value, 0 ), // If input checked or not.
            __( 'Yes', 'wp-spotim' ) // Translated text.
        );

        // No template
        $escaped_template .= sprintf($radio_template,
            esc_attr( $args['name'] ), // Input's name.
            $no_value, // Input's value.
            checked( $args['value'], $no_value, 0 ), // If input checked or not.
            __( 'No', 'wp-spotim' ) // Translated text.
        );

        // Description template
        if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
            $description_template = sprintf( '<p class="description">%s</p>', $args['description'] );
            $escaped_template .= $description_template;
        }

        echo $escaped_template;
    }

    public static function text_field( $args ) {
        $args = self::set_name( $args );
        $args['value'] = sanitize_text_field( $args['value'] );
        $text_template = '<input name="%s" type="text" value="%s" />';

        // Text input template
        $escaped_template = sprintf($text_template,
            esc_attr( $args['name'] ), // Input's name.
            esc_attr( $args['value'] ) // Input's value.
        );

        // Description template
        if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
            $description_template = sprintf( '<p class="description">%s</p>', $args['description'] );
            $escaped_template .= $description_template;
        }

        echo $escaped_template;
    }
}
