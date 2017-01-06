<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SpotIM_Options {
    private static $instance;
    private $data;
    public $slug, $option_group;

    protected function __construct() {
        $this->slug = 'wp-spotim-settings';
        $this->option_group = 'wp-spotim-options';
        $this->default_options = array(
            'enable_comments_on_page' => 0,
            'enable_comments_replacement' => 1,
            'import_token' => '',
            'page_number' => 0,
            'posts_per_request' => 10,
            'spot_id' => ''
        );

        $this->data = $this->get_meta_data();
    }

    public static function get_instance() {
        if ( is_null( static::$instance ) ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function create_options() {
        update_option( $this->slug, $this->default_options );

        return $this->default_options;
    }

    private function get_meta_data() {
        $data = get_option( $this->slug, array() );

        if ( empty( $data ) ) {
            $data = $this->create_options();
        } else {
            $data['enable_comments_replacement'] = intval( $data['enable_comments_replacement'] );
            $data['enable_comments_on_page'] = intval( $data['enable_comments_on_page'] );

            $data = array_merge( $this->default_options, $data );
        }

        return $data;
    }

    public function get( $key = '', $default_value = false ) {
        return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default_value;
    }

    public function update( $name, $value ) {
        $new_option = array();
        $new_option[ $name ] = $value;

        // validate new option and retrive with old ones to update as a whole
        $options = $this->validate( $new_option );

        $options_updated = update_option( $this->slug, $options );


        if ( $options_updated ) {
            $this->data = $options;
        }

        // return updated value
        return $this->data[ $name ];
    }

    public function reset( $name ) {
        $value = $this->get( $name );

        switch( gettype( $value ) ) {
            case 'number':
                $value = 0;
                break;
            case 'string':
                $value = '';
                break;
            case 'boolean':
            default:
                $value = false;
        }

        return $this->update( $name, $value );
    }

    public function validate( $input ) {
        $options = $this->get_meta_data();

        foreach ( $input as $key => $value ) {
            switch( $key ) {
                case 'enable_comments_replacement':
                case 'enable_comments_on_page':
                    $options[ $key ] = intval( $value );
                    break;
                case 'posts_per_request':
                    $value = absint( $value );
                    $options[ $key ] = 0 === $value ? 1 : $value;
                    break;
                case 'page_number':
                    $options[ $key ] = absint( $value );
                    break;
                case 'spot_id':
                case 'import_token':
                default:
                    $options[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }

        return $options;
    }

    public function require_file( $path = '', $return_path = false ) {
        $valid = validate_file( $path );

        if ( 0 === $valid ) {
            if ( $return_path ) {
                $output = $path;
            } else {
                require_once( $path );
                $output = $valid;
            }
        } else {
            if ( $return_path ) {
                $output = '';
            } else {
                $output = $valid;
            }
        }

        return $output;
    }

    public function require_template( $path = '', $return_path = false ) {
        $path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' . $path;

        return $this->require_file( $path, $return_path );
    }

    public function require_javascript( $path = '', $return_path = false ) {
        $path = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/javascripts/' . $path;

        return $this->require_file( $path, $return_path );
    }

    public function require_stylesheet( $path = '', $return_path = false ) {
        $path = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/stylesheets/' . $path;

        return $this->require_file( $path, $return_path );
    }
}
