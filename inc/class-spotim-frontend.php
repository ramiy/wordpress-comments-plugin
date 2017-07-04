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
     *
     * @param SpotIM_Options $options Plugin options.
     *
     * @return void
     */
    public function __construct( $options ) {

        // Set options
        self::$options = $options;

        $embed_method = self::$options->get( 'embed_method' );
        $display_priority = self::$options->get( 'display_priority' );

        // SpotIM Recirculation
        add_action( 'the_content', array( __CLASS__, 'add_spotim_recirculation' ), $display_priority );

        // SpotIM Comments
        add_action( 'wp_footer', array( __CLASS__, 'comments_footer_scripts' ) );
        if ( $embed_method == 'content' ) {

            // Add after the content
            add_action( 'the_content', array( __CLASS__, 'the_content_comments_template' ), $display_priority );
            add_filter( 'comments_template', array( __CLASS__, 'empty_comments_template' ), 20 );

        } else {

            // Replace the WordPress comments
            add_filter( 'comments_template', array( __CLASS__, 'filter_comments_template' ), 20 );
            add_filter( 'comments_number', array( __CLASS__, 'filter_comments_number' ), 20 );

        }

    }

    /**
     * Has Spot.im comments
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function has_spotim_comments() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( ! in_array( self::$options->get( "display_{$post->post_type}" ), array( 'comments', 'comments_recirculation' ), true ) )
            return false;

        // Bail if Spot.IM Comments are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_display_comments', true );
        $specific_display = in_array( $specific_display, array( 'enable', 'disable' ), true ) ? $specific_display : 'enable';
        if ( 'disable' === $specific_display )
            return false;

        // Return true if all tests passed
        return true;
    }

    /**
     * Empty comments template
     *
     * @since 4.1.0
     *
     * @access public
     * @static
     *
     * @param string $template Comments template to load.
     *
     * @return string
     */
    public static function empty_comments_template( $template ) {

        if ( self::has_spotim_comments() ) {

            // Load empty comments template
            $require_template_path = self::$options->require_template( 'comments-template-empty.php', true );
            if ( ! empty( $require_template_path ) ) {
                $template = $require_template_path;
            }

        }

        return $template;
    }

    /**
     * Filter comments template
     *
     * @since 4.1.0
     *
     * @access public
     * @static
     *
     * @param string $template Comments template to load.
     *
     * @return string
     */
    public static function the_content_comments_template( $content ) {

        if ( self::has_spotim_comments() ) {

            // Load SpotIM comments template
            ob_start();
            include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/comments-template.php' );
            $content .= ob_get_contents();
            ob_end_clean();

        }

        return $content;

    }

    /**
     * Filter comments template
     *
     * @since 1.0.2
     *
     * @access public
     * @static
     *
     * @param string $template Comments template to load.
     *
     * @return string
     */
    public static function filter_comments_template( $template ) {

        if ( self::has_spotim_comments() ) {
            $spot_id = self::$options->get( 'spot_id' );

            /**
             * Before loading SpotIM comments template
             *
             * @since 4.0.0
             *
             * @param string $template Comments template to load.
             * @param int    $spot_id  SpotIM ID.
             */
            $template = apply_filters( 'before_spotim_comments', $template, $spot_id );

            // Load SpotIM comments template
            $require_template_path = self::$options->require_template( 'comments-template.php', true );
            if ( ! empty( $require_template_path ) ) {
                $template = $require_template_path;
            }

            /**
             * After loading SpotIM comments template
             *
             * @since 4.0.0
             *
             * @param string $template Comments template to load.
             * @param int    $spot_id  SpotIM ID.
             */
            $template = apply_filters( 'after_spotim_comments', $template, $spot_id );
        }

        return $template;
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
     * Has Spot.im questions
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function has_spotim_questions() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( ! in_array( self::$options->get( "display_{$post->post_type}" ), array( 'comments', 'comments_recirculation' ), true ) )
            return false;

        // Bail if Spot.IM questions are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_display_question', true );
        if ( empty( $specific_display ) )
            return false;

        // Return true if all tests passed
        return true;
    }

    /**
     * Has Spot.im recirculation
     *
     * @since 4.0.0
     *
     * @access public
     * @static
     *
     * @return bool
     */
    public static function has_spotim_recirculation() {
        global $post;

        // Bail if it's not a single template
        if ( ! ( is_single() || is_page() ) )
            return false;

        // Bail if comments are closed
        if ( ! comments_open() )
            return false;

        // Bail if Spot.IM is disabled for this post type
        if ( 'comments_recirculation' !== self::$options->get( "display_{$post->post_type}" ) )
            return false;

        // Bail if Spot.IM Recirculation are disabled for this this specific content item
        $specific_display = get_post_meta( absint( $post->ID ), 'spotim_display_recirculation', true );
        $specific_display = in_array( $specific_display , array( 'enable', 'disable' ), true ) ? $specific_display : 'enable';
        if ( 'disable' === $specific_display )
            return false;

        // Return true if all tests passed
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
     * @param string $content The post content.
     *
     * @return bool
     */
    public static function add_spotim_recirculation( $content ) {

        if ( self::has_spotim_recirculation() ) {
            $spot_id = self::$options->get( 'spot_id' );

            /**
             * Befor loading SpotIM recirculation template
             *
             * @since 4.0.0
             *
             * @param string $content The post content.
             * @param int    $spot_id SpotIM ID.
             */
            $content = apply_filters( 'before_spotim_recirculation', $content, $spot_id );

            // Load SpotIM recirculation template
            ob_start();
            include( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/recirculation-template.php' );
            $content .= ob_get_contents();
            ob_end_clean();

            /**
             * After loading SpotIM recirculation template
             *
             * @since 4.0.0
             *
             * @param string $content The post content.
             * @param int    $spot_id SpotIM ID.
             */
            $content = apply_filters( 'after_spotim_recirculation', $content, $spot_id );
        }

        return $content;
    }
}
