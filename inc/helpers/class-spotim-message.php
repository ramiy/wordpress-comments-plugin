<?php

define( 'COMMENT_IMPORT_AGENT', 'Spot.IM/1.0 (Export)' );

class SpotIM_Message {
    private $messages_map;
    private $message_data = array();
    private $comment_data;
    private $users;
    private $post_id;

    public function __construct( $message, $users, $post_id ) {
        $this->message = $message;
        $this->users = $users;
        $this->post_id = absint( $post_id );

        // $this->messages_map = $this->create_messages_map();
        $this->comment_data = $this->create_comment_data();
    }

    // public function update_messages_map( $comment_id ) {}

    public function is_comment_exists() {
        // $sp_comments_ids_map = get_post_meta( $post_id, 'spotim_messages_map', true );
        $comment_exists = false;

        // if ( ! isset( $comments_ids_map[ $sp_comment->sp_comment_id ] ) ) {
            $comments_args = array(
                'parent' => absint( $this->comment_data[ 'comment_parent' ] ),
                'post_id' => absint( $this->post_id ),
                'status' => 'approve',
                'user_id' => 0
            );

            $comments = get_comments( $comments_args );

            if ( ! empty( $comments ) ) {
                while ( ! empty( $comments ) ) {
                    $comment = array_shift( $comments );

                    if ( $comment->comment_author === $this->comment_data[ 'comment_author' ] &&
                        $comment->comment_author_email === $this->comment_data[ 'comment_author_email' ] &&
                        $comment->comment_content === $this->comment_data[ 'comment_content' ] &&
                        $comment->comment_date === $this->comment_data[ 'comment_date' ] &&
                        absint( $comment->comment_parent ) === absint( $this->comment_data[ 'comment_parent' ] ) ) {

var_dump('HODOR, no way jose');
                        // $this->update_messages_map( $comment->comment_id );
                        $comment_exists = true;
                        break;
                    }
                }
            }
        // }

        return $comment_exists;
    }

    public function get_comment_data() {
        return $this->comment_data;
    }

    private function get_comment_parent_id() {
        $comment_parent_id = 0;

        if ( isset( $this->message->comment_id ) ) {
            $this->messages_map = get_post_meta( $this->post_id, 'spotim_messages_map', true );

            if ( isset( $this->messages_map[ $this->message->id ] ) ) {
                $comment_parent_id = $this->messages_map[ $sp_message->id ];
            }
        }

        return $comment_parent_id;
    }

    private function create_comment_data() {
        $author = $this->get_comment_author();
        $comment_parent = $this->get_comment_parent_id();
        $date = date('Y-m-d H:i:s', absint( $this->message->written_at ) );
        $date_gmt = get_gmt_from_date( $date );

        return array(
            'comment_agent' => COMMENT_IMPORT_AGENT,
            'comment_approved' => 1,
            'comment_author' => $author[ 'name' ],
            'comment_author_email' => $author[ 'email' ],
            'comment_author_url' => '',
            'comment_content' => wp_kses_post( $this->message->content ),
            'comment_date' => $date,
            'comment_date_gmt' => $date_gmt,
            'comment_parent' => $comment_parent,
            'comment_post_ID' => absint( $this->post_id ),
            'comment_type' => 'comment',
            'user_id' => 0
        );
    }

    private function get_comment_author() {
        $author = array(
            'email' => '',
            'name' => 'Guest'
        );

        // set author's name
        if ( isset( $this->message->user_id ) &&
             isset( $this->users->{ $this->message->user_id }->user_name ) ) {
            $author[ 'name' ] = sanitize_text_field( $this->users->{ $this->message->user_id }->user_name );
        }

        // set author's email
        if ( isset( $this->message->user_id ) &&
             isset( $this->users->{ $this->message->user_id }->email ) &&
             is_email( $this->users->{ $this->message->user_id }->email ) ) {
            $author[ 'email' ] = $this->users->{ $this->message->user_id }->email;
        }

        return $author;
    }
}