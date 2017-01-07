<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SpotIM_Frontend
 *
 * Plugin frontend.
 *
 * @since 1.0.2
 */
class SpotIM_Frontend {

    /**
     * Options
     *
     * @since 2.0.2
     *
     * @access private
     * @static
     *
     * @var SpotIM_Options
     */
    private static $options;

    /**
     * Launch
     *
     * @since 2.0.0
     *
     * @access public
     * @static
     *
     * @param SpotIM_Options $options Plugin options.
     *
     * @return void
     */
    public static function launch( $options ) {
        self::$options = $options;

        add_filter( 'comments_template', array( __CLASS__, 'filter_comments_template' ), 20 );
        add_filter( 'comments_number', array( __CLASS__, 'filter_comments_number' ), 20 );
        add_action( 'wp_footer', array( __CLASS__, 'action_wp_footer' ) );
    }

    /**
     * Allow comments on page
     *
     * @since 2.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function allow_comments_on_page() {
        $are_comments_allowed = false;

        if ( is_page() && comments_open() ) {
            if ( 1 === self::$options->get( 'enable_comments_on_page' ) ) {
                $are_comments_allowed = true;
            }
        }

        return $are_comments_allowed;
    }

    /**
     * Allow comments on single
     *
     * @since 2.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function allow_comments_on_single() {
        $are_comments_allowed = false;

        if ( is_single() && comments_open() ) {
            if ( 1 === self::$options->get( 'enable_comments_replacement' ) ) {
                $are_comments_allowed = true;
            }
        }

        return $are_comments_allowed;
    }

    /**
     * Filter comments template
     *
     * @since 1.0.2
     *
     * @access public
     * @static
     *
     * @param string $comments_template Comments template file to load.
     *
     * @return string
     */
    public static function filter_comments_template( $comments_template ) {
        if ( self::allow_comments_on_page() || self::allow_comments_on_single() ) {
            $require_template_path = self::$options->require_template( 'comments-template.php', true );

            if ( ! empty( $require_template_path ) ) {
                $comments_template = $require_template_path;
            }
        }

        return $comments_template;
    }

    /**
     * Filter comments number
     *
     * @since 1.0.5
     *
     * @access public
     * @static
     *
     * @param string $count Text for no comments.
     *
     * @return string
     */
	 public static function filter_comments_number( $count ) {
        global $post;

        return '<span class="spot-im-replies-count" data-post-id="' . absint( $post->ID ) . '"></span>';
    }

    /**
     * Add to wp_footer
     *
     * @since 1.0.2
     *
     * @access public
     * @static
     *
     * @return void
     */
    public static function action_wp_footer() {
        $spot_id = self::$options->get( 'spot_id' );

        if ( ! empty( $spot_id ) ) {
            self::$options->require_template( 'embed-template.php' );
        }
    }
}
