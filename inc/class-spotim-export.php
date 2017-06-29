<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SpotIM_Export
 *
 * Plugin export class.
 *
 * @since 4.0.0
 */
class SpotIM_Export extends SpotIM_Endpoint {

	/**
	 * Export access token
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $secret;

	/**
	 * Constructor
	 *
	 * Get things started.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		parent::__construct();
		$this->secret = $this->options->get( 'plugin_secret' );
		$this->endpoint = 'spot_im_wp_endpoint';
	}

	/**
	 * Add query vars
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'plugin_secret';
		$vars[] = 'post_id';
		return $vars;
	}

	/**
	 * Do endpoint
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function do_endpoint() {

		if ( false === ( $post_data = $this->get_data() ) ) {
			return;
		}

		if ( empty( $post_data ) || ! is_object( $post_data ) || empty( $post_data->post_id ) || empty( $post_data->plugin_secret ) ) {
			$post_id = 'NO-ID';
			$plugin_secret = false;
		}
		else {
			$post_id = $post_data->post_id;
			$plugin_secret = $post_data->plugin_secret;
		}
		if ( $plugin_secret !== $this->secret || ! is_numeric( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			status_header( 200 );
			wp_send_json( array( 'success' => 'false', 'reason' => 'bad secret or bad id' ) );
		}
		status_header( 200 );
		$comments = $this->get_comments_data( $post_id );
		wp_send_json( $comments );
	}

	/**
	 * Get comments data
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function get_comments_data( $post_id ) {

		$comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
		$commentsData = array(
			'comments_ids' => array(),
			'tree' => array(),
			'messages' => array(),
			'users' => array(),
		);
		foreach ( $comments as $comment ) {
			$id = $comment->comment_ID;
			$commentsData['comments_ids'][] = $id;
			$this->add_to_tree( $commentsData['tree'], $id, $comment->comment_parent );
			$user_id = $this->get_comment_user( $comment );
			$this->add_user( $commentsData['users'], $user_id, $comment->comment_author );
			$commentsData['messages'][ $id ] =
				array(
					'user_id'    => $user_id,
					'content'    => preg_replace( '[ ]+', ' ', strip_tags( str_replace( '<', ' <', $comment->comment_content ) ) ),
					'written_at' => strtotime( $comment->comment_date ),
				);
		}

		return $commentsData;
	}

	/**
	 * Add to tree
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function add_to_tree( &$commentsTree, $id, $comment_parent ) {
		if ( ! array_key_exists( $id, $commentsTree ) ) {
			$commentsTree[ $id ] = array();
		}
		if ( $comment_parent ) {
			if ( ! array_key_exists( $comment_parent, $commentsTree ) ) {
				$commentsTree[ $comment_parent ] = array();
			}
			$commentsTree[ $comment_parent ][] = $id;
		}
	}

	/**
	 * Add user
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function add_user( &$users, $user_id, $user_name ) {
		if ( ! array_key_exists( $user_id, $users ) ) {
			$users[ $user_id ] = array( 'user_name' => $user_name );
		}
	}

	/**
	 * Get comment user
	 *
	 * @since 4.0.0
	 *
	 * @access private
	 */
	private function get_comment_user( $comment ) {
		if ( $comment->user_id ) {
			return $comment->user_id;
		}
		if ( $comment->comment_author ) {
			return $comment->comment_author;
		}
		if ( $comment->comment_author_email ) {
			return $comment->comment_author_email;
		}
		return esc_html__( 'Unknown', 'spotim-comments' );
	}
}
