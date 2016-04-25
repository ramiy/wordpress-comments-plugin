<?php

class SpotIM_Options {
    private static $instance;
    private $data;
    public $templates_path, $slug;

    protected function __construct() {
        $this->templates_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/';
        $this->slug = 'wp-spotim-settings';
        $this->data = $this->get_options();
    }

    public static function get_instance() {
        if ( is_null( static::$instance ) ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function get_options() {
        if ( isset( $this->data ) && is_array( $this->data ) &&
            ! empty( $this->data ) ) {
            return $this->data;
        }

        return apply_filters( 'spotim_options', get_option( $this->slug, array() ) );
    }

    public function get( $key = '', $default_value = false ) {
        $options = $this->get_options();

        return ! empty( $options[ $key ] ) ? $options[ $key ] : $default_value;
    }

    // public function update( $key, $value ) {}
}
