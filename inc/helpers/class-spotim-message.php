<?php

define( 'SPOTIM_COMMENT_IMPORT_AGENT', 'Spot.IM/1.0 (Export)' );

class SpotIM_Message {
    private $messages_map;
    private $message_data;
    private $comment_data;
    private $users;
    private $post_id;

    public function __construct( $type, $message, $users, $post_id ) {
        $this->message = $message;
        $this->users = count( (array) $users ) ? $users : new stdClass();
        $this->post_id = absint( $post_id );

        $this->messages_map = $this->get_messages_map();

        switch( $type ) {
            case 'new':
                $this->comment_data = $this->new_comment_data();
                break;
            case 'update':
                $this->comment_data = $this->update_comment_data();
                break;
            case 'delete':
                break;
            case 'soft_delete':
                $this->comment_data = $this->soft_delete_comment_data();
                break;
            case 'anonymous_comment':
                $this->comment_data = $this->anonymous_comment_data();
                break;
        }
    }

    public function is_comment_exists() {
        $comment_exists = false;

        if ( ! $this->get_comment_id() ) {
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

                        $this->update_messages_map( $comment->comment_ID );

                        $comment_exists = true;

                        break;
                    }
                }
            }
        } else {
            $comment_exists = true;
        }

        return $comment_exists;
    }

    public function get_comment_data() {
        return $this->comment_data;
    }

    public function get_comment_id() {
        $comment_id = 0;

        if ( isset( $this->messages_map[ $this->message->id ] ) ) {
            $comment_id = $this->messages_map[ $this->message->id ]['comment_id'];
        }

        return $comment_id;
    }

    public function update_messages_map( $comment_id ) {
        $this->messages_map[ $this->message->id ] = array(
            'comment_id' => $comment_id
        );

        if ( isset( $this->message->comment_id ) ) {
            $this->messages_map[ $this->message->id ]['parent_message_id'] = $this->message->comment_id;
        }

        return update_post_meta( $this->post_id, 'spotim_messages_map', $this->messages_map );
    }

    public function get_message_and_children_ids_map() {
        $messages_map[ $this->message->id ] = $this->messages_map[ $this->message->id ]['comment_id'];

        foreach( $this->messages_map as $message_id => $message ) {
            if ( isset( $message['parent_message_id'] ) &&
                 $this->message->id === $message['parent_message_id'] ) {
                $messages_map[ $message_id ] = $message['comment_id'];
            }
        }

        return $messages_map;
    }

    public function delete_from_messages_map( $message_id ) {
        if ( isset( $this->messages_map[ $message_id ] ) ) {
            unset( $this->messages_map[ $message_id ] );
            return update_post_meta( $this->post_id, 'spotim_messages_map', $this->messages_map );
        } else {
            return true;
        }
    }

    private function get_comment_parent_id() {
        $comment_parent_id = 0;

        if ( isset( $this->message->comment_id ) ) {
            if ( isset( $this->messages_map[ $this->message->comment_id ] ) ) {
                $comment_parent_id = $this->messages_map[ $this->message->comment_id ]['comment_id'];
            }
        }

        return $comment_parent_id;
    }

    private function get_messages_map() {
        $messages_map = get_post_meta( $this->post_id, 'spotim_messages_map', true );

        if ( is_string( $messages_map ) ) {
            $messages_map = array();

            add_post_meta( $this->post_id, 'spotim_messages_map', $messages_map );
        }

        return $messages_map;
    }

    private function new_comment_data() {
        $author = $this->get_comment_author();
        $comment_parent = $this->get_comment_parent_id();
        $date = date( 'Y-m-d H:i:s', absint( $this->message->written_at ) );
        $date_gmt = get_gmt_from_date( $date );

        return array(
            'comment_agent' => SPOTIM_COMMENT_IMPORT_AGENT,
            'comment_approved' => 1,
            'comment_author' => $author['name'],
            'comment_author_email' => $author['email'],
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

    private function update_comment_data() {
        $data = array();

        $data['comment_ID'] = absint( $this->get_comment_id() );
        $data['comment_post_ID'] = absint( $this->post_id );

        if ( ! empty( $this->message->content ) ) {
            $data['comment_content'] = wp_kses_post( $this->message->content );
        }

        $parent_comment_id = absint( $this->get_comment_parent_id() );

        if ( $parent_comment_id ) {
            $data['comment_parent'] = $parent_comment_id;
        }

        return $data;
    }

    private function soft_delete_comment_data() {
        $data = $this->anonymous_comment_data();

        $data['comment_content'] = esc_html__( 'This message was deleted.', 'wp-spotim' );

        return $data;
    }

    private function anonymous_comment_data() {
        $data = $this->update_comment_data();
        $author = $this->get_comment_author();

        $data['comment_author'] = $author['name'];
        $data['comment_author_email'] = $author['email'];

        return $data;
    }

    private function get_comment_author() {
        $author = array(
            'email' => '',
            'name' => 'Guest'
        );

        if ( isset( $this->message->user_id ) ) {

            // set author's name
            if ( isset( $this->users->{ $this->message->user_id }->nick_name ) ) {
                $author['name'] = sanitize_text_field(
                    $this->users->{ $this->message->user_id }->nick_name
                );
            } else if ( isset( $this->users->{ $this->message->user_id }->user_name ) ) {
                $author['name'] = sanitize_text_field(
                    $this->users->{ $this->message->user_id }->user_name
                );
            }

            // set author's email
            if ( isset( $this->users->{ $this->message->user_id }->email ) &&
                 is_email( $this->users->{ $this->message->user_id }->email ) ) {
                $author['email'] = $this->users->{ $this->message->user_id }->email;
            }
        }

        return $author;
    }
}