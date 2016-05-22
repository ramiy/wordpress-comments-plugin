<?php

class SpotIM_Frontend {
    private static $options;

    public static function launch( $options ) {
        self::$options = $options;

        add_filter( 'comments_template', array( __CLASS__, 'filter_comments_template' ), 1 );
        add_filter( 'comments_number', array( __CLASS__, 'filter_comments_number' ), 1 );
        add_action( 'wp_footer', array( __CLASS__, 'action_wp_footer' ) );
    }

    public static function allow_comments_on_page() {
        $are_comments_allowed = false;

        if ( is_page() && comments_open() ) {
            if ( 1 === self::$options->get( 'enable_comments_on_page' ) ) {
                $are_comments_allowed = true;
            }
        }

        return $are_comments_allowed;
    }

    public static function allow_comments_on_single() {
        $are_comments_allowed = false;

        if ( is_single() && comments_open() ) {
            if ( 1 === self::$options->get( 'enable_comments_replacement' ) ) {
                $are_comments_allowed = true;
            }
        }

        return $are_comments_allowed;
    }

    public static function filter_comments_template( $comments_template ) {
        if ( self::allow_comments_on_page() || self::allow_comments_on_single() ) {
            $comments_template = self::$options->templates_path . 'comments-template.php';
        }

        return $comments_template;
    }

    public static function filter_comments_number( $count ) {
        global $post;

        return '<span class="spot-im-replies-count" data-post-id="' . $post->ID . '"></span>';
    }

    public static function action_wp_footer() {
        $spot_id = self::$options->get( 'spot_id' );

        if ( ! empty( $spot_id ) ) {
            require_once( self::$options->templates_path . 'embed-template.php' );
        }
    }
}
