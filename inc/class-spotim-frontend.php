<?php

class SpotIM_Frontend {
    private $admin, $templates_path;

    public function __construct( $admin ) {
        $this->admin = $admin;
        $this->templates_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/';
    }

    public function launch() {
        add_filter( 'comments_template', array( $this, 'filter_comments_template' ), 1 );
        add_filter( 'comments_number', array( $this, 'filter_comments_number' ), 1 );
        add_action( 'wp_footer', array( $this, 'action_wp_footer' ) );
    }

    public function filter_comments_template( $theme_template ) {
        $switch_comments_template = false;

        if ( is_page() && comments_open() ) {
            $allow_comments = $this->admin->get_option( 'enable_comments_on_page' ) == '1';

            if ( $allow_comments ) {
                $switch_comments_template = true;
            }
        } else {
            $allow_comments = $this->admin->get_option( 'enable_comments_replacement' ) == '1';

            if ( $allow_comments && is_single() && comments_open() ) {
                $switch_comments_template = true;
            }
        }

        if ( $switch_comments_template ) {
            $theme_template = $this->templates_path . 'comments-template.php';
        }

        return $theme_template;
    }

    public function filter_comments_number( $count ) {
        global $post;

        return '<span class="spot-im-replies-count" data-post-id="' . $post->ID . '"></span>';
    }

    public function action_wp_footer() {
        $spot_id = $this->admin->get_option( 'spot_id' );

        require_once( $this->templates_path . 'embed-template.php' );
    }
}
