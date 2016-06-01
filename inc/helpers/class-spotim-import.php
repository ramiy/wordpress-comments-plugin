<?php

define( 'COMMENT_IMPORT_AGENT', 'Spot.IM/1.0 (Export)' );

class SpotIM_Import {
    public function __construct( $options ) {
        $this->options = $options;
    }

    public function start() {
        $post_ids = $this->get_post_ids();
        // $responses = array();

        if ( ! empty( $post_ids ) ) {

            while ( ! empty( $post_ids ) ) {
                $post_id = array_shift( $post_ids );

                $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

                $url = add_query_arg( array(
                    'spot_id' => $this->options->get( 'spot_id' ),
                    'post_id' => $post_id,
                    'etag' => absint( $post_etag )
                ), IMPORT_URL );

                $response = wp_remote_retrieve_body(
                    wp_remote_get( $url, array( 'sslverify' => true ) )
                );

                // $responses[] = $response;
                // $responses[] = json_decode( $response );

                if ( ! empty( $response ) ) {
                    $this->sync_comments( $response, $post_etag );
                }
            }
        }

        return 'HODOR';
        // return $responses;
    }

    private function sync_comments( $json, $post_etag ) {
        $stream = json_decode( $json );

        if ( $stream->from_etag < $stream->new_etag ) {
            $users = count( (array) $stream->users ) ? $stream->users : new stdClass();

            foreach ( $stream->events as $event ) {
                switch ( $event->type ) {
                    case 'c+':
                    case 'r+':
                        $this->add_new_comment( $event->message, $users, $stream->post_id );
                        break;
                    case 'c~':
                    case 'r~':
                        $this->update_comment( $event->message, $users, $stream->post_id );
                        break;
                    case 'c-':
                    case 'r-':
                        $this->delete_comment( $event->message, $users, $stream->post_id );
                        break;
                    case 'c*':
                        $this->soft_delete_comment( $event->message, $users, $stream->post_id );
                        break;
                    case 'c@':
                    case 'r@':
                        $this->anonymous_comment( $event->message, $users, $stream->post_id );
                        break;
                }
            }

            // update_post_meta( $stream->post_id, 'spotim_etag', absint( $stream->new_etag ), $post_etag );
        }
    }

    private function get_post_ids() {
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'post',
            'post_status' => 'publish',
            'fields' => 'ids'
        );

        return get_posts( $args );
    }

    private function is_wp_comment_exists( $sp_comment, $post_id ) {
        // $sp_comments_ids_map = get_post_meta( $post_id, 'spotim_messages_map', true );
        $wp_comment_exists = false;

        // if ( ! isset( $comments_ids_map[ $sp_comment->sp_comment_id ] ) ) {
            $wp_comments_args = array(
                'parent' => absint( $sp_comment[ 'comment_parent' ] ),
                'post_id' => absint( $post_id ),
                'status' => 'approve',
                'user_id' => 0
            );

            $wp_comments = get_comments( $wp_comments_args );

            if ( ! empty( $wp_comments ) ) {
                while ( ! empty( $wp_comments ) ) {
                    $wp_comment = array_shift( $wp_comments );

                    if ( $wp_comment->comment_author === $sp_comment[ 'comment_author' ] &&
                        $wp_comment->comment_author_email === $sp_comment[ 'comment_author_email' ] &&
                        $wp_comment->comment_content === $sp_comment[ 'comment_content' ] &&
                        $wp_comment->comment_date === $sp_comment[ 'comment_date' ] &&
                        absint( $wp_comment->comment_parent ) === absint( $sp_comment[ 'comment_parent' ] ) ) {
                        // $new_sp_comments_ids_map = $sp_comments_ids_map;
                        // $new_sp_comments_ids_map[ $sp_comment->id ] = $wp_comment->comment_ID;

                        // update_post_meta(
                        //     $post_id,
                        //     'spotim_messages_map',
                        //     $new_sp_comments_ids_map,
                        //     $sp_comments_ids_map
                        // );
var_dump('HODOR, no way jose');
                        $wp_comment_exists = true;
                        break;
                    }
                }
            }
        // }

        return $wp_comment_exists;
    }

    private function get_wp_comment_parent_id( $sp_message, $post_id ) {
        $wp_comment_parent_id = 0;

        if ( isset( $sp_message->comment_id ) ) {
            $sp_messages_map = get_post_meta( $post_id, 'spotim_messages_map', true );

            if ( isset( $sp_messages_map[ $sp_message->id ] ) ) {
                $wp_comment_parent_id = $sp_messages_map[ $sp_message->id ];
            }
        }

        return $wp_comment_parent_id;
    }

    private function create_wp_comment_data( $sp_message, $sp_users, $post_id ) {
        $wp_author = $this->get_comment_author_from_message( $sp_message, $sp_users );
        $wp_comment_parent = $this->get_wp_comment_parent_id( $sp_message, $post_id );
        $wp_date = date('Y-m-d H:i:s', absint( $sp_message->written_at ) );
        $wp_date_gmt = get_gmt_from_date( $wp_date );

        return array(
            'comment_agent' => COMMENT_IMPORT_AGENT,
            'comment_approved' => 1,
            'comment_author' => $wp_author[ 'name' ],
            'comment_author_email' => $wp_author[ 'email' ],
            'comment_author_url' => '',
            'comment_content' => wp_kses_post( $sp_message->content ),
            'comment_date' => $wp_date,
            'comment_date_gmt' => $wp_date_gmt,
            'comment_parent' => $wp_comment_parent,
            'comment_post_ID' => absint( $post_id ),
            'comment_type' => 'comment',
            'user_id' => 0,
            'sp_comment_id' => $sp_message->id
        );
    }

    private function get_comment_author_from_message( $sp_message, $sp_users ) {
        $author = array(
            'email' => '',
            'name' => 'Guest'
        );

        // set author's name
        if ( isset( $sp_message->user_id ) &&
             isset( $sp_users->{ $sp_message->user_id }->user_name ) ) {
            $author[ 'name' ] = sanitize_text_field( $sp_users->{ $sp_message->user_id }->user_name );
        }

        // set author's email
        if ( isset( $sp_message->user_id ) &&
             isset( $sp_users->{ $sp_message->user_id }->email ) &&
             is_email( $sp_users->{ $sp_message->user_id }->email ) ) {
            $author[ 'email' ] = $sp_users->{ $sp_message->user_id }->email;
        }

        return $author;
    }

    private function add_new_comment( $sp_message, $sp_users, $post_id ) {
        $comment_created = false;

        $sp_messages_map = get_post_meta( $post_id, 'spotim_messages_map', true );
        $wp_comment_data = $this->create_wp_comment_data( $sp_message, $sp_users, $post_id );

        if ( ! $this->is_wp_comment_exists( $wp_comment_data, $post_id ) ) {
            $comment_id = wp_insert_comment( $wp_comment_data );
var_dump('HODOR, shall give a man a pass.');

            // if ( $comment_id ) {
                // $new_sp_messages_map = $sp_messages_map;
                // $new_sp_messages_map[ $sp_message->id ] = $comment_id;

                // update_post_meta(
                //     $post_id,
                //     'spotim_messages_map',
                //     $new_sp_messages_map,
                //     $sp_messages_map
                // );
                // $comment_created = true;
            // }
        }

        return $comment_created;
    }
    private function update_comment( $message, $users, $post_id ) {
        var_dump('update_comment');
        // return true;
    }
    private function delete_comment( $message, $users, $post_id ) {
        var_dump('delete_comment');
        // return true;
    }
    private function soft_delete_comment( $message, $users, $post_id ) {
        var_dump('soft_delete_comment');
        // return true;
    }
    private function anonymous_comment( $message, $users, $post_id ) {
        var_dump('anonymous_comment');
        // return true;
    }
}