<?php

final class SpotIM_Settings_Fields {
    public static function general_settings_section_header() {
        ?>
            <p><?php _e( 'These are some basic settings for SpotIM.', 'wp-spotim' ); ?></p>
        <?php
    }

    public static function raw_html( $args ) {
        if ( empty( $args['html'] ) ) {
            return;
        }

        echo $args['html'];

        if ( ! empty( $args['desc'] ) ) :
            ?>
                <p class="description">
                    <?php echo $args['desc']; ?>
                </p>
            <?php
        endif;
    }

    public static function text_field( $args ) {
        $args = self::set_name_and_value( $args );

        $args = wp_parse_args( $args, array(
            'classes' => array()
        ));

        if ( empty( $args['id'] ) || empty( $args['page'] ) ) {
            return;
        }

        ?>
        <input type="text"
            id="<?php echo esc_attr( $args['id'] ); ?>"
            name="<?php echo esc_attr( $args['name'] ); ?>"
            value="<?php echo esc_attr( $args['value'] ); ?>"
            class="<?php echo implode( ' ', $args['classes'] ); ?>" />

        <?php if (!empty($desc)) : ?>
            <p class="description"><?php echo $desc; ?></p>
        <?php
        endif;
    }

    public static function yesno_field( $args ) {
        $args = self::set_name_and_value( $args );
        ?>

        <label class="tix-yes-no description">
            <input type="radio"
                name="<?php echo esc_attr( $args['name'] ); ?>"
                value="1"
                <?php checked( $args['value'], true ); ?> /> <?php _e( 'Yes', 'wp-spotim' ); ?>
        </label>

        <label class="tix-yes-no description">
            <input type="radio"
                name="<?php echo esc_attr( $args['name'] ); ?>"
                value="0" <?php checked( $args['value'], false ); ?> /> <?php _e( 'No', 'wp-spotim' ); ?>
        </label>

        <?php if ( isset( $args['description'] ) ) : ?>
            <p class="description">
                <?php echo $args['description']; ?>
            </p>
        <?php endif; ?>
        <?php
    }

    private static function set_name_and_value( $args ) {
        if ( ! isset( $args['name'] ) ) {
            $args['name'] = sprintf(
                '%s[%s]', esc_attr( $args['page'] ), esc_attr( $args['id'] )
            );
        }

        if ( ! isset( $args['value'] ) ) {
            $args['value'] = SpotIM_Options::get_instance()->get( $args['id'] );
        }

        return $args;
    }
}