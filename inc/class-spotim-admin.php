<?php

class SpotIM_Admin {
    private $hook;
    protected $_screens = array();
    protected $options;
    public $slug = 'wp-spotim-settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function create_admin_menu() {
        $this->_screens['main'] = add_menu_page(
            __( 'Spot.IM Settings', 'wp-spotim' ),
            __( 'Spot.IM', 'wp-spotim' ),
            'manage_options',
            $this->slug,
            array( $this, 'admin_page_callback' )
        );

        // Just make sure we are create instance.
        add_action( 'load-' . $this->_screens['main'], array( $this, 'load_cb' ) );
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

    /**
     * Returns all options
     *
     * @since 0.1
     * @return array
     */
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

    public function load_cb() {}
}

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
            $args['value'] = WP_SpotIM::instance()->admin->get_option( $args['id'] );
        }

        return $args;
    }
}
