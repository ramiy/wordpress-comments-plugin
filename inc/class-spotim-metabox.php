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

	public function __construct() {

		if ( is_admin() ) {
			add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
			add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
		}

	}

	public function init_metabox() {

		add_action( 'add_meta_boxes',        array( $this, 'add_metabox' )         );
		add_action( 'save_post',             array( $this, 'save_metabox' ), 10, 2 );

	}

	public function add_metabox() {

		add_meta_box(
			'spotim',
			__( 'Spot.im', 'spotim-comments' ),
			array( $this, 'render_metabox' ),
			'post',
			'advanced',
			'default'
		);

	}

	public function render_metabox( $post ) {

		// Add nonce for security and authentication.
		wp_nonce_field( 'nonce_action', 'nonce' );

		// Retrieve an existing value from the database.
		$spotim_display_comments = get_post_meta( $post->ID, 'spotim_display_comments', true );
		$spotim_display_question = get_post_meta( $post->ID, 'spotim_display_question', true );
		$spotim_question_text = get_post_meta( $post->ID, 'spotim_question_text', true );

		// Set default values.
		if( empty( $spotim_display_comments ) ) $spotim_display_comments = '';
		if( empty( $spotim_display_question ) ) $spotim_display_question = '';
		if( empty( $spotim_question_text ) ) $spotim_question_text = '';

		// Form fields.
		echo '<table class="form-table">';

		echo '	<tr>';
		echo '		<th><label for="spotim_display_comments" class="spotim_display_comments_label">' . __( 'Comments', 'spotim-comments' ) . '</label></th>';
		echo '		<td>';
		echo '			<select id="spotim_display_comments" name="spotim_display_comments" class="spotim_display_comments_field">';
		echo '			<option value="default" ' . selected( $spotim_display_comments, 'default', false ) . '> ' . __( 'Use Global Settings', 'spotim-comments' ) . '</option>';
		echo '			<option value="1" ' . selected( $spotim_display_comments, '1', false ) . '> ' . __( 'Enable', 'spotim-comments' ) . '</option>';
		echo '			<option value="0" ' . selected( $spotim_display_comments, '0', false ) . '> ' . __( 'Disable', 'spotim-comments' ) . '</option>';
		echo '			</select>';
		echo '			<p class="description">' . __( 'Display settings for Spot.im comments. Use global settings or custom settings for this item.', 'spotim-comments' ) . '</p>';
		echo '		</td>';
		echo '	</tr>';

		echo '	<tr>';
		echo '		<th><label for="spotim_display_question" class="spotim_display_question_label">' . __( 'Question', 'spotim-comments' ) . '</label></th>';
		echo '		<td>';
		echo '			<select id="spotim_display_question" name="spotim_display_question" class="spotim_display_question_field">';
		echo '			<option value="default" ' . selected( $spotim_display_question, 'default', false ) . '> ' . __( 'Use Global Settings', 'spotim-comments' ) . '</option>';
		echo '			<option value="1" ' . selected( $spotim_display_question, '1', false ) . '> ' . __( 'Enable', 'spotim-comments' ) . '</option>';
		echo '			<option value="0" ' . selected( $spotim_display_question, '0', false ) . '> ' . __( 'Disable', 'spotim-comments' ) . '</option>';
		echo '			</select>';
		echo '			<p class="description">' . __( 'Display settings for Spot.im question. Use global settings or custom settings for this item.', 'spotim-comments' ) . '</p>';
		echo '		</td>';
		echo '	</tr>';

		echo '	<tr>';
		echo '		<th><label for="spotim_question_text" class="spotim_question_text_label">' . __( 'Question Text', 'spotim-comments' ) . '</label></th>';
		echo '		<td>';
		echo '			<input type="text" id="spotim_question_text" name="spotim_question_text" class="spotim_question_text_field" placeholder="' . esc_attr__( '', 'spotim-comments' ) . '" value="' . esc_attr__( $spotim_question_text ) . '">';
		echo '			<p class="description">' . __( 'The question text to display.', 'spotim-comments' ) . '</p>';
		echo '		</td>';
		echo '	</tr>';

		echo '</table>';

	}

	public function save_metabox( $post_id, $post ) {

		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
		$nonce_action = 'nonce_action';

		// Check if a nonce is set.
		if ( ! isset( $nonce_name ) )
			return;

		// Check if a nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) )
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
		$new_spotim_display_comments = isset( $_POST[ 'spotim_display_comments' ] ) ? $_POST[ 'spotim_display_comments' ] : '';
		$new_spotim_display_question = isset( $_POST[ 'spotim_display_question' ] ) ? $_POST[ 'spotim_display_question' ] : '';
		$new_spotim_question_text = isset( $_POST[ 'spotim_question_text' ] ) ? sanitize_text_field( $_POST[ 'spotim_question_text' ] ) : '';

		// Update the meta field in the database.
		update_post_meta( $post_id, 'spotim_display_comments', $new_spotim_display_comments );
		update_post_meta( $post_id, 'spotim_display_question', $new_spotim_display_question );
		update_post_meta( $post_id, 'spotim_question_text', $new_spotim_question_text );

	}

}
