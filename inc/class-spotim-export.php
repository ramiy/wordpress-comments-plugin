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
//		$this->export_access_token = $this->options->get( 'export_access_token' );
	}

	/*
	public function get_export_endpoint() {
			return home_url( '/?name='.$this->endpoint );
	}

	public function get_export_access_token() {
		if ( ! empty( $this->export_access_token ) )
			return $this->export_access_token;

		if ( empty( $this->secret ) )
			return false;

		$connect_data = [ 'plugin_secret' => $this->secret];
		$result = $this->rest_get_data( 'export/access-token', $connect_data );

		if ( $result->success != 'true' ) {
			$this->options->update( 'error', $result->error_code );
			return false;
		}
		$this->options->update( 'error', '' );

		$this->options->update( 'export_access_token', $result->export_access_token );

//		$admin_notices = $this->options->get( 'admin_notices' );
//		$this->options->update( 'admin_notices', "$admin_notices &nbsp; Export access token: {$result->export_access_token}" );
		return $this->export_access_token;
	}
	*/

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
			wp_send_json( [ 'success' => 'false', 'reason' => 'bad secret or bad id' ] );
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

		$comments = get_comments( [ 'post_id' => $post_id, 'status' => 'approve' ] );
		$commentsData = [
			'comments_ids' => [],
			'tree' => [],
			'messages' => [],
			'users' => [],
		];
		foreach ( $comments as $comment ) {
			$id = $comment->comment_ID;
			$commentsData[ 'comments_ids' ][] = $id;
			$this->add_to_tree( $commentsData[ 'tree' ], $id, $comment->comment_parent );
			$user_id = $this->get_comment_user( $comment );
			$this->add_user( $commentsData[ 'users' ], $user_id, $comment->comment_author );
			$commentsData[ 'messages' ][ $id ] =
				[
					'user_id'    => $user_id,
					'content'    => preg_replace( '[ ]+', ' ', strip_tags( str_replace( '<', ' <', $comment->comment_content ) ) ),
					'written_at' => strtotime( $comment->comment_date ),
				];
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
			$commentsTree[ $id ] = [];
		}
		if ( $comment_parent ) {
			if ( ! array_key_exists( $comment_parent, $commentsTree ) ) {
				$commentsTree[ $comment_parent ] = [];
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
			$users[$user_id] = [ 'user_name' => $user_name];
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
