<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SpotIM_Form_Helper
 *
 * Form helpers.
 *
 * @since 3.0.0
 */
class SpotIM_Form_Helper {

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
            sanitize_text_field( $yes_value ), // Input's value.
            checked( $args['value'], $yes_value, 0 ), // If input checked or not.
            esc_html__( 'Yes', 'spotim-comments' ) // Translated text.
        );

        // No template
        $escaped_template .= sprintf($radio_template,
            esc_attr( $args['name'] ), // Input's name.
            sanitize_text_field( $no_value ), // Input's value.
            checked( $args['value'], $no_value, 0 ), // If input checked or not.
            esc_html__( 'No', 'spotim-comments' ) // Translated text.
        );

        // Description template
        if ( isset( $args['description'] ) ) {
            $escaped_template .= self::get_description_html( $args['description'] );
        }

        echo $escaped_template;
    }

    public static function radio_fields( $args ) {
        $args = self::set_name( $args );
        $radio_template = '<label class="description"><input type="radio" name="%s" value="%s" %s /> %s &nbsp;&nbsp;&nbsp;</label>';
        $escaped_template = '';

		foreach ( $args['fields'] as $key => $value ) {
            $escaped_template .= sprintf($radio_template,
                esc_attr( $args['name'] ), // Input's name.
                sanitize_text_field( $key ), // Input's value.
                checked( $args['value'], $key, 0 ), // If input checked or not.
                $value // Translated text.
            );
		}

        // Description template
        if ( isset( $args['description'] ) ) {
            $escaped_template .= self::get_description_html( $args['description'] );
        }

        echo $escaped_template;
    }

    public static function text_field( $args ) {
        $args = self::set_name( $args );
        $args['value'] = sanitize_text_field( $args['value'] );
        $text_template = '<input name="%s" type="text" value="%s" autocomplete="off" />';

        // Text input template
        $escaped_template = sprintf($text_template,
            esc_attr( $args['name'] ), // Input's name.
            esc_attr( $args['value'] ) // Input's value.
        );

        // Description template
        if ( isset( $args['description'] ) ) {
            $escaped_template .= self::get_description_html( $args['description'] );
        }

        echo $escaped_template;
    }

    public static function button( $args ) {
        $button_template = '<button id="%s" class="button button-primary">%s</button>';

        $escaped_template = sprintf($button_template,
            esc_attr( $args['id'] ), // Button's id.
            esc_attr( $args['text'] ) // Button's text.
        );

        // Description template
        if ( isset( $args['description'] ) ) {
            $escaped_template .= self::get_description_html( $args['description'] );
        }

        echo $escaped_template;
    }

    public static function import_button( $args ) {

        // Import button template
        $button_template = '<button id="%s" class="button button-primary">%s</button>';
        $escaped_template = sprintf($button_template,
            esc_attr( $args['import_button']['id'] ), // Button's id.
            esc_attr( $args['import_button']['text'] ) // Button's text.
        );

        // Cancel import link
        $link_template = '<a href="#cancel" id="%s" class="">%s</a>';
        $escaped_template .= sprintf($link_template,
            esc_attr( $args['cancel_import_link']['id'] ), // Link's id.
            esc_attr( $args['cancel_import_link']['text'] ) // Link's text.
        );

        // Description template
        $escaped_template .= self::get_description_html();

        echo $escaped_template;
    }

    private static function get_description_html( $text = '' ) {
        return sprintf( '<p class="description">%s</p>', wp_kses_post( $text ) );
    }
}
