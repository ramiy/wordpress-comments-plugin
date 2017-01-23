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

        // SpotIM Comments
        add_filter( 'comments_template', array( __CLASS__, 'filter_comments_template' ), 20 );
        add_filter( 'comments_number', array( __CLASS__, 'filter_comments_number' ), 20 );
        add_action( 'wp_footer', array( __CLASS__, 'comments_footer_scripts' ) );

        // SpotIM Questions
        add_filter( 'before_displaying_spotim_comments', array( __CLASS__, 'add_spotim_questions' ), 10, 2 );

        // SpotIM Recirculation
        add_action( 'the_content', array( __CLASS__, 'add_spotim_recirculation' ) );
    }

    /**
     * Display Spot.im comments
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function display_spotim_comments() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( ! self::$options->get( "display_{$post->post_type}" ) )
            return false;

        // Bail if Spot.IM Comments are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_display_comments', true );
        $specific_display = in_array( $specific_display , array( 'enable', 'disable' ) ) ? $specific_display : 'enable';
        if ( 'disable' === $specific_display )
            return false;

        // If all tests passed - show SpotIM
        return true;
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
        if ( self::display_spotim_comments() ) {
            $spot_id = self::$options->get( 'spot_id' );

            /**
             * Befor loading SpotIM comments template
             *
             * @since 4.0.0
             *
             * @param string $comments_template Comments template file to load.
             * @param int    $spot_id           SpotIM ID.
             */
            $comments_template = apply_filters( 'before_displaying_spotim_comments', $comments_template, $spot_id );

            // Load SpotIM comments template
            $require_template_path = self::$options->require_template( 'comments-template.php', true );
            if ( ! empty( $require_template_path ) ) {
                $comments_template = $require_template_path;
            }

            /**
             * After loading SpotIM comments template
             *
             * @since 4.0.0
             *
             * @param string $comments_template Comments template file to load.
             * @param int    $spot_id           SpotIM ID.
             */
            $comments_template = apply_filters( 'after_displaying_spotim_comments', $comments_template, $spot_id );
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
     * Comments JS in the footer
     *
     * @since 1.0.2
     *
     * @access public
     * @static
     *
     * @return void
     */
    public static function comments_footer_scripts() {
        $spot_id = self::$options->get( 'spot_id' );

        if ( ! empty( $spot_id ) ) {
            self::$options->require_template( 'embed-template.php' );
        }
    }

    /**
     * Display Spot.im questions
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function display_spotim_questions() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( ! self::$options->get( "display_{$post->post_type}" ) )
            return false;

        // Bail if Spot.IM questions are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_question_text', true );
        if ( empty( $specific_display ) )
            return false;

        // If all tests passed - show SpotIM
        return true;
    }

    /**
     * Add Spot.im questions
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @param string $comments_template Comments template file to load.
     * @param int    $spot_id           SpotIM ID.
     *
     * @return bool
     */
    public static function add_spotim_questions( $comments_template, $spot_id ) {
        if ( self::display_spotim_questions() ) {
            //$spot_id = self::$options->get( 'spot_id' );
            $comments_template .= include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/questions-template.php' );
        }
        return $comments_template;
    }

    /**
     * Display Spot.im recirculation
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function display_spotim_recirculation() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( ! self::$options->get( "display_{$post->post_type}" ) )
            return false;

        // Bail if Spot.IM Recirculation are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_display_comments', true );
        $specific_display = in_array( $specific_display , array( 'enable', 'disable' ) ) ? $specific_display : 'enable';
        if ( 'disable' === $specific_display )
            return false;

        // If all tests passed - show SpotIM
        return true;
    }

    /**
     * Add Spot.im recirculation to the content
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function add_spotim_recirculation( $content ) {
        if ( self::display_spotim_recirculation() ) {
            $spot_id = self::$options->get( 'spot_id' );
            $content .= include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/recirculation-template.php' );
        }

        return $content;
    }
}
