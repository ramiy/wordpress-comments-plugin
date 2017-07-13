<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SpotIM_JSON_Feed
 *
 * JSON feed.
 *
 * @since 4.1.0
 */
class SpotIM_JSON_Feed {

    /**
     * Post ID
     *
     * @since 4.1.0
     *
     * @access private
     *
     * @var int
     */
    private $post_id;

    /**
     * Comments
     *
     * @since 4.1.0
     *
     * @access private
     *
     * @var array
     */
    private $comments;

    /**
     * Conversation
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @var array
     */
    public $conversation;

    /**
     * Messages
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @var array
     */
    public $messages;

    /**
     * Users
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @var array
     */
    public $users;

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 4.1.0
     *
     * @access public
     */
    public function __construct( $post_id ) {
        $this->post_id = $post_id;

        // Load post comments
        $comments_query = new WP_Comment_Query();
        $comments = $comments_query->query( apply_filters( 'spotim_json_feed_query_args', array(
            'status' => 'approve',
            'post_id' => $post_id,
        ) ) );

        // Structure Comments
        foreach ( $comments as $comment ) {
            $this->comments[ $comment->comment_ID ] = $comment;
        }

        // Aggregate Data
        $this->conversation = $this->aggregate_conversation();
        $this->messages = $this->aggregate_messages();
        $this->users = $this->aggregate_users();

        // Return JSON
        return apply_filters( 'spotim_json_feed', json_encode( $this ) );
    }

    /**
     * Has Comments
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return bool
     */
    public function has_comments() {
        return empty( $this->comments );
    }

    /**
     * Get Comment Count
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return int
     */
    public function get_comment_count() {
        return count( $this->comments );
    }

    /**
     * Has Parent Comment
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @param obj $comment Comment object.
     *
     * @return bool
     */
    public static function has_parent_comment( $comment ) {
        return ( 0 == $comment->comment_parent );
    }

    /**
     * Get Top Level Comments
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return array
     */
    public function get_top_level_comments() {
        return array_filter( $this->comments, array( $this, 'has_parent_comment' ) );
    }

    /**
     * Get Children
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @param int $parent_id Parrent comment ID.
     *
     * @return array
     */
    public function get_children( $parent_id ) {
        $children = array();
        foreach ( $this->comments as $comment ) {
            if ( $comment->comment_parent == $parent_id )
                $children[] = $comment;
        }
        return $children;
    }

    /**
     * Get Tree
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return object
     */
    public function get_tree() {
        $tree = array();
        $parent_comments = $this->get_top_level_comments();
        foreach ( $parent_comments as $comment ) {
            $this->traverse( $comment->comment_ID, $tree );
        }
        return (object) $tree;
    }

    /**
     * Traverse
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return void
     */
    private function traverse( $comment_id, &$bank ) {
        $child_comments = $this->get_children($comment_id);
        // if no comments under this one, we're ending it here
        if ( ! empty($child_comments) ) {
            $bank[$comment_id] = wp_list_pluck( $child_comments, 'comment_ID' );
            // recurse down the tree
            foreach ( $child_comments as $comment )
                $this->traverse($comment->comment_ID, $bank);
        }
    }

    /**
     * Aggregate Conversation
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return array
     */
    public function aggregate_conversation() {
        $conversation = array();
        $conversation['post_id'] = $this->post_id;
        $conversation['published_at'] = get_the_time( 'U', $this->post_id );
        $conversation['conversation_url'] = get_the_permalink( $this->post_id );
        $conversation['comments_ids'] = array_reverse( array_values( wp_list_pluck( $this->get_top_level_comments(), 'comment_ID' ) ) );
        $conversation['tree'] = $this->get_tree();
        return $conversation;
    }

    /**
     * Aggregate Messages
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return array
     */
    public function aggregate_messages() {
        $messages = array();
        foreach ( $this->comments as $comment_id => $comment ) {
            $messages[ $comment->comment_ID ]['content'] = apply_filters( 'get_comment_text', $comment->comment_content, $comment, array() );
            $messages[ $comment->comment_ID ]['written_at'] = strtotime( $comment->comment_date_gmt );

            if ( ! trim( $comment->comment_author_email ) ) {
                // Comment without an email
                $messages[ $comment->comment_ID ]['anonymous'] = true;
            } else {
                // Registered User
                if ( ! $registered_user ) {
                    // Anonymous comment
                    $messages[ $comment->comment_ID ]['anonymous'] = true;
                } else {
                    $messages[ $comment->comment_ID ]['anonymous'] = false;
                    $messages[ $comment->comment_ID ]['user_id'] = $registered_user->id;
                }
                //$registered_user = get_user_by( 'email', $comment->comment_author_email );
            }
        }
        return $messages;
    }

    /**
     * Aggregate Users
     *
     * @since 4.1.0
     *
     * @access public
     *
     * @return array
     */
    public function aggregate_users() {
        $users = array();
        foreach ( $this->comments as $comment_id => $comment ) {
            if ( ! trim( $comment->comment_author_email ) ) {
                // Comment without an email - HIDE
                //$users[ $comment->comment_author_email ]['email'] = '';
                //$users[ $comment->comment_author_email ]['display_name'] = $comment->comment_author;
            } else {
                // Registered User
                $registered_user = get_user_by( 'email', $comment->comment_author_email );
                if ( ! $registered_user ) {
                    // User doesn't exists - HIDE
                    //$users[ $comment->comment_author_email ]['email'] = $comment->comment_author_email;
                    //$users[ $comment->comment_author_email ]['display_name'] = $comment->comment_author;
                } else {
                    // User exists - SHOW
                    //$users[ $registered_user->id ]['email'] = $comment->comment_author_email;
                    $users[ $registered_user->id ]['display_name'] = $comment->comment_author;
                    $users[ $registered_user->id ]['user_name'] = $registered_user->user_login;
                    $users[ $registered_user->id ]['avatar'] = esc_url( get_avatar_url( $registered_user->ID ) );
                }
            }
        }
        return $users;
    }

}
