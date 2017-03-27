<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Spotim_Meta_Box
 *
 * Plugin meta box displayed in posts and pages.
 *
 * @since 4.0.0
 */
class Spotim_Meta_Box {

    /**
     * Options
     *
     * @since 4.0.0
     *
     * @access private
     * @static
     *
     * @var SpotIM_Options
     */
    private static $options;

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @param SpotIM_Options $options Plugin options.
     */
    public function __construct( $options ) {

        if ( is_admin() ) {
            self::$options = $options;
            add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
            add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
        }

    }

    /**
     * Init Meta Box
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function init_metabox() {

        add_action( 'add_meta_boxes',        array( $this, 'add_metabox' )         );
        add_action( 'save_post',             array( $this, 'save_metabox' ), 10, 2 );

    }

    /**
     * Add Meta Box
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function add_metabox() {

        // Default screen where the boxes should be display in
        $screen = array();

        // Available post types
        $post_types = get_post_types( array( 'public' => true ) );

        // Apply metabox only to selected post types
        foreach ( $post_types as $post_type ) {

            // only for post types where comments or recirculation are enabled
            if ( in_array( self::$options->get( "display_{$post_type}" ), array( 'comments', 'comments_recirculation' ), true ) ) {
                $screen[] = $post_type;
            }

        }

        // Bail if no post types selected
        if ( empty( $screen ) )
            return;

        // Add metaboxes to selected post types
        add_meta_box(
            'spotim',
            esc_html__( 'Spot.IM', 'spotim-comments' ),
            array( $this, 'render_metabox' ),
            $screen,
            'advanced',
            'default'
        );

    }

    /**
     * Render Meta Box
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function render_metabox( $post ) {

        // Add nonce for security and authentication.
        wp_nonce_field( 'nonce_action', 'nonce' );

        // Retrieve an existing value from the database.
        $spotim_display_comments      = get_post_meta( $post->ID, 'spotim_display_comments', true );
        $spotim_display_question      = get_post_meta( $post->ID, 'spotim_display_question', true );
        $spotim_display_recirculation = get_post_meta( $post->ID, 'spotim_display_recirculation', true );

        // Set default values.
        if ( empty( $spotim_display_comments      ) ) $spotim_display_comments      = 'enable';
        if ( empty( $spotim_display_question      ) ) $spotim_display_question      = '';
        if ( empty( $spotim_display_recirculation ) ) $spotim_display_recirculation = 'enable';

        // Form fields.
        echo '<table class="form-table">';

        echo '    <tr>';
        echo '		<th><label for="spotim_display_comments" class="spotim_display_comments_label">' . esc_html__( 'Comments', 'spotim-comments' ) . '</label></th>';
        echo '		<td>';
        echo '			<select id="spotim_display_comments" name="spotim_display_comments" class="spotim_display_comments_field">';
        echo '			<option value="enable" '  . selected( $spotim_display_comments, 'enable',  false ) . '> ' . esc_html__( 'Enable', 'spotim-comments' ) . '</option>';
        echo '			<option value="disable" ' . selected( $spotim_display_comments, 'disable', false ) . '> ' . esc_html__( 'Disable', 'spotim-comments' ) . '</option>';
        echo '			</select>';
        echo '			<p class="description">' . esc_html__( 'Show Spot.IM comments.', 'spotim-comments' ) . '</p>';
        echo '		</td>';
        echo '	</tr>';

        echo '	<tr>';
        echo '		<th><label for="spotim_display_question" class="spotim_display_question_label">' . esc_html__( 'Community Question', 'spotim-comments' ) . '</label></th>';
        echo '		<td>';
        echo '			<input type="text" id="spotim_display_question" name="spotim_display_question" class="spotim_display_question_field" value="' . esc_attr( $spotim_display_question ) . '">';
        echo '			<p class="description">' . esc_html__( 'Show Spot.IM community question.', 'spotim-comments' ) . '</p>';
        echo '		</td>';
        echo '	</tr>';

        if ( 'comments_recirculation' === self::$options->get( "display_{$post->post_type}" ) ) {
            echo '	<tr>';
            echo '		<th><label for="spotim_display_recirculation" class="spotim_display_recirculation_label">' . esc_html__( 'Recirculation', 'spotim-comments' ) . '</label></th>';
            echo '		<td>';
            echo '			<select id="spotim_display_recirculation" name="spotim_display_recirculation" class="spotim_display_recirculation_field">';
            echo '			<option value="enable" '  . selected( $spotim_display_recirculation, 'enable',  false ) . '> ' . esc_html__( 'Enable', 'spotim-comments' ) . '</option>';
            echo '			<option value="disable" ' . selected( $spotim_display_recirculation, 'disable', false ) . '> ' . esc_html__( 'Disable', 'spotim-comments' ) . '</option>';
            echo '			</select>';
            echo '			<p class="description">' . esc_html__( 'Show Spot.IM recirculation.', 'spotim-comments' ) . '</p>';
            echo '		</td>';
            echo '	</tr>';
        }

        echo '</table>';

    }

    /**
     * Save Meta Box
     *
     * @since 4.0.0
     *
     * @access public
     *
     * @return void
     */
    public function save_metabox( $post_id, $post ) {

        // Check if a nonce is set and is valid.
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'nonce_action' ) )
            return;

        // Check if the user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) )
            return;

        // Check if it's not an autosave.
        if ( wp_is_post_autosave( $post_id ) )
            return;

        // Check if it's not a revision.
        if ( wp_is_post_revision( $post_id ) )
            return;

        // Sanitize user input.
        $new_spotim_display_comments = isset( $_POST['spotim_display_comments'] ) ? sanitize_text_field( $_POST['spotim_display_comments'] ) : '';
        $new_spotim_display_question = isset( $_POST['spotim_display_question'] ) ? sanitize_text_field( $_POST['spotim_display_question'] ) : '';
        if ( 'comments_recirculation' === self::$options->get( "display_{$post->post_type}" ) ) {
            $new_spotim_display_recirculation = isset( $_POST['spotim_display_recirculation'] ) ? sanitize_text_field( $_POST['spotim_display_recirculation'] ) : '';
        }

        // Update the meta field in the database.
        update_post_meta( $post_id, 'spotim_display_comments', $new_spotim_display_comments );
        update_post_meta( $post_id, 'spotim_display_question', $new_spotim_display_question );
        if ( 'comments_recirculation' === self::$options->get( "display_{$post->post_type}" ) ) {
            update_post_meta( $post_id, 'spotim_display_recirculation', $new_spotim_display_recirculation );
        }

    }

}
