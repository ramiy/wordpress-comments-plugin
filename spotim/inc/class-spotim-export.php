<?php
require_once 'class-spotim-rest-api.php';

class SpotIM_Export extends SpotIM_RestAPI {
	private $options, $secret, $endpoint/*, $export_access_token*/;

	public function __construct() {
		$this->options = SpotIM_Options::get_instance();
		$this->secret = $this->options->get('plugin_secret');
		$this->endpoint = 'spot_im_wp_endpoint';
//		$this->export_access_token = $this->options->get('export_access_token');
	}

	/*	public function get_export_endpoint() {
			return home_url('/?name='.$this->endpoint);
		}
		public function get_export_access_token() {
			if (!empty($this->export_access_token))
				return $this->export_access_token;

			if (empty($this->secret))
				return false;

			$connect_data = ['plugin_secret' => $this->secret];
			$result = $this->rest_get_data('export/access-token', $connect_data);

			if ($result->success != 'true') {
				$this->options->update('error', $result->error_code);
				return false;
			}
			$this->options->update('error', '');

			$this->options->update('export_access_token', $result->export_access_token);

	//		$admin_notices = $this->options->get('admin_notices');
	//		$this->options->update('admin_notices', "$admin_notices &nbsp; Export access token: {$result->export_access_token}");
			return $this->export_access_token;
		}	*/

	public function add_endpoint() {

		global $wp_rewrite;
		$wp_rewrite->add_endpoint($this->endpoint, EP_ROOT);
//		$wp_rewrite->add_endpoint($this->endpoint.'_unittest', EP_ROOT);		// DEBUG only
		$wp_rewrite->flush_rules();
		add_action('template_redirect', array($this, 'do_export'));
		add_filter('query_vars', array($this, 'add_query_vars'));
		add_filter('wp_headers', array($this, 'access_control_allow_origin'), 91, 1);
	}

	/// Hooks (actions/filters) methods:

	public function access_control_allow_origin( $headers ) {

		$headers['Access-Control-Allow-Origin'] = get_http_origin(); // Can't use wildcard origin, instead use the requesting origin
		$headers['Access-Control-Allow-Credentials'] = 'true';
		$headers['Access-Control-Allow-Methods'] = 'POST';
		$headers['Access-Control-Allow-Headers'] = 'Content-Type';

		return $headers;
	}

	public function add_query_vars($vars) {
		$vars[] = 'plugin_secret';
		$vars[] = 'post_id';
		return $vars;
	}

	public function do_export() {

		$name = get_query_var('name');
		if ($name != $this->endpoint) {
/*			if ($name == $this->endpoint.'_unittest') {		// DEBUG only
				status_header(200);
				wp_send_json($this->rest_get_data_url(home_url('/?name='.$this->endpoint), '', ['plugin_secret' => $this->secret, 'post_id' => 1]));
			}*/
			return;
		}
		try {
			$post_data = json_decode( $this->get_raw_data() );
		}
		catch (Exception $e) {}
		if (empty($post_data) || !is_object($post_data) || empty($post_data->post_id) || empty($post_data->plugin_secret)) {
			$post_id = 'NO-ID';
			$plugin_secret = false;
		}
		else {
			$post_id = $post_data->post_id;
			$plugin_secret = $post_data->plugin_secret;
		}
		if ($plugin_secret !== $this->secret || !is_numeric($post_id) || 'publish' !== get_post_status($post_id)) {
			status_header(200);
			wp_send_json(['success' => 'false', 'reason' => 'bad secret or bad id'
//				, 'my_plugin_secret' => $this->secret
//				, 'plugin_secret' => $plugin_secret
//				, 'post_id' => $post_id
			]);
		}
		status_header(200);
		$comments = $this->get_comments_data($post_id);
		wp_send_json($comments);
	}

	/// Private methods:

	private function get_comments_data($post_id) {

		$comments = get_comments(['post_id' => $post_id, 'status' => 'approve']);
		$commentsData = [
			'comments_ids' => [],
			'tree' => [],
			'messages' => [],
			'users' => [],
		];
		foreach ($comments as $comment) {
			$id = $comment->comment_ID;
			$commentsData['comments_ids'][] = $id;
			$this->add_to_tree($commentsData['tree'], $id, $comment->comment_parent);
			$user_id = $this->get_comment_user($comment);
			$this->add_user($commentsData['users'], $user_id, $comment->comment_author);
			$commentsData['messages'][$id] =
				[
					'user_id'    => $user_id,
					'content'    => preg_replace('[ ]+', ' ', strip_tags(str_replace('<', ' <', $comment->comment_content))),
					'written_at' => strtotime($comment->comment_date),
				];
		}

		return $commentsData;
	}

	private function add_to_tree(&$commentsTree, $id, $comment_parent) {
		if (!array_key_exists($id, $commentsTree)) {
			$commentsTree[$id] = [];
		}
		if ($comment_parent) {
			if (!array_key_exists($comment_parent, $commentsTree)) {
				$commentsTree[$comment_parent] = [];
			}
			$commentsTree[$comment_parent][] = $id;
		}
	}

	private function add_user(&$users, $user_id, $user_name) {
		if (!array_key_exists($user_id, $users)) {
			$users[$user_id] = ['user_name' => $user_name];
		}
	}

	private function get_comment_user($comment) {
		if ($comment->user_id) {
			return $comment->user_id;
		}
		if ($comment->comment_author) {
			return $comment->comment_author;
		}
		if ($comment->comment_author_email) {
			return $comment->comment_author_email;
		}
		return 'Unknown';
	}

	// Copied from class WP_REST_Server
	private function get_raw_data() {
		global $HTTP_RAW_POST_DATA;

		/*
		 * A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
		 * but we can do it ourself.
		 */
		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
	}
}

