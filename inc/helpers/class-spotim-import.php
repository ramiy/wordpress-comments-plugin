<?php

// define( 'JSONSTUB_EXPORT_URL', 'http://jsonstub.com/export/wordpress/anonymous/reply' );

class SpotIM_Import {
    private $options, $posts_per_page, $page_number;

    public function __construct( $options ) {
        $this->options = $options;

        $this->posts_per_page = 50;
        $this->page_number = 0;
    }

    public function start( $spot_id, $import_token, $page_number = 0 ) {

        // save spot_id and import_token in plugin's options meta
        $this->options->update( 'spot_id', $spot_id );
        $this->options->update( 'import_token', $import_token );

        $this->page_number = absint( $page_number );
        $post_ids = $this->get_post_ids( $this->posts_per_page, $this->page_number );

        if ( ! empty( $post_ids ) ) {
            // import comments data from Spot.IM
            $streams = array();
            $streams = $this->fetch_comments( $post_ids );

            // sync comments data with wordpress comments
            $this->merge_comments( $streams );
        }

        //
        $this->finish();
    }

    private function finish() {
        $response_args = array(
            'status' => '',
            'message' => ''
        );

        $total_posts_count = count( $this->get_post_ids() );
        $current_posts_count = $this->posts_per_page;

        if ( 0 < $this->page_number ) {
            $current_posts_count = $current_posts_count + ( $this->posts_per_page * $this->page_number );
        }

        if ( 0 === $total_posts_count ) {
            $response_args['status'] = 'success';
            $response_args['message'] = __( 'Your website doesn\'t have any published blog posts', 'wp-spotim' );
        } else if ( $current_posts_count < $total_posts_count ) {
            $translated_message = __( '%d / %d posts are synchronize comments.', 'wp-spotim' );
            $parsed_message = sprintf( $translated_message, $current_posts_count, $total_posts_count );

            $response_args['status'] = 'continue';
            $response_args['message'] = $parsed_message;
        } else {
            $response_args['status'] = 'success';
            $response_args['message'] = __( 'Your comments are up to date.', 'wp-spotim' );
        }

        $this->response( $response_args );
    }

    private function fetch_comments( $post_ids = array() ) {
        $streams = array();

        while ( ! empty( $post_ids ) ) {
            $post_id = array_shift( $post_ids );
            $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

            $stream = $this->request( array(
                'spot_id' => $this->options->get( 'spot_id' ),
                'post_id' => $post_id,
                'etag' => absint( $post_etag ),
                'count' => 1000,
                'token' => $this->options->get( 'import_token' )
            ) );
            if ( $stream->is_ok ) {
                $streams[] = $stream->body;
            } else {
                $this->response( array(
                    'status' => 'error',
                    'message' => $stream->body
                ) );
            }
        }

        return $streams;
    }

    private function merge_comments( $streams = array() ) {
        while ( ! empty( $streams ) ) {
            $stream = array_shift( $streams );

            if ( $stream->from_etag < $stream->new_etag ) {
                $sync_status = $this->sync_comments(
                    $stream->events,
                    $stream->users,
                    $stream->post_id
                );

                if ( ! $sync_status ) {
                    $translated_error = __(
                        'Could not import comments of from this stream: %s', 'wp-spotim'
                    );

                    $this->response( array(
                        'status' => 'error',
                        'message' => sprintf( $translated_error, json_encode( $stream ) )
                    ) );
                }
            }

            update_post_meta(
                $stream->post_id,
                'spotim_etag',
                absint( $stream->new_etag ),
                absint( $stream->from_etag )
            );
        }
    }

    private function request( $query_args ) {
        $url = add_query_arg( $query_args, SPOTIM_EXPORT_URL );

        $result = new stdClass();
        $result->is_ok = false;

        $response = wp_remote_get( $url, array( 'sslverify' => true ) );

        if ( ! is_wp_error( $response ) &&
             'OK' === wp_remote_retrieve_response_message( $response ) &&
             200 === wp_remote_retrieve_response_code( $response ) ) {

            $response_body = json_decode( wp_remote_retrieve_body( $response ) );

            if ( isset( $response_body->success ) && false === $response_body->success ) {
                $result->is_ok = false;
            } else {
                $result->is_ok = true;
                $result->body = $response_body;
            }
        }

        if ( ! $result->is_ok ) {
            $translated_error = __( 'Retriving data failed from this URL: %s', 'wp-spotim' );

            $result->body = sprintf( $translated_error, esc_attr( $url ) );
        }

        return $result;
    }

    public function response( $args = array() ) {
        $statuses_list = array( 'continue', 'success', 'error' );

        $defaults = array(
            'status' => '',
            'message' => ''
        );

        if ( ! empty( $args ) ) {
            $args = array_merge( $defaults, $args );

            if ( ! empty( $args['status'] ) && ! empty( $args['message'] ) ) {
                $args['message'] = sanitize_text_field( $args['message'] );

                if ( in_array( $args['status'], $statuses_list ) ) {
                    wp_send_json( $args );
                }
            }
        }
    }

    private function request_mock() {
        $retrieved_body = wp_remote_retrieve_body(
            wp_remote_get( JSONSTUB_EXPORT_URL, array(
                'headers' => array(
                    'JsonStub-User-Key'     => '0fce8d12-9e2c-45c9-9284-e8c6d57a6fe1',
                    'JsonStub-Project-Key'  => '08e0f77f-5dce-4576-b3b2-4f3ed49c1e67',
                    'Content-Type'          => 'application/json'
                )
            ) )
        );

        $data = json_decode( $retrieved_body );
        $data->is_ok = true;

        return $data;
    }

    private function get_post_ids( $posts_per_page = -1, $page_number = 0 ) {
        $args = array(
            'posts_per_page' => $posts_per_page,
            'post_type' => array( 'post' ),
            'post_status' => 'publish',
            'orderby' => 'id',
            'order' => 'ASC',
            'fields' => 'ids'
        );

        if ( -1 !== $posts_per_page ) {
            $args['offset'] = $posts_per_page * $page_number;
        }

        if ( 1 === $this->options->get( 'enable_comments_on_page' ) ) {
            $args['post_type'][] = 'page';
        }

        return get_posts( $args );
    }

    private function sync_comments( $events, $users, $post_id ) {
        $flag = true;

        if ( ! empty( $events ) ) {
            foreach ( $events as $event ) {

                switch ( $event->type ) {
                    case 'c+':
                    case 'r+':
                        $flag = $this->add_new_comment( $event->message, $users, $post_id );
                        break;
                    case 'c~':
                    case 'r~':
                        $flag = $this->update_comment( $event->message, $users, $post_id );
                        break;
                    case 'c-':
                    case 'r-':
                        $flag = $this->delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c*':
                        $flag = $this->soft_delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c@':
                    case 'r@':
                        $flag = $this->anonymous_comment( $event->message, $users, $post_id );
                        break;
                }

                if ( ! $flag ) {
                    break;
                }
            }
        }

        return $flag;
    }

    private function add_new_comment( $sp_message, $sp_users, $post_id ) {
        $comment_created = false;

        $message = new SpotIM_Message( 'new', $sp_message, $sp_users, $post_id );

        if ( ! $message->is_comment_exists() ) {
            $comment_id = wp_insert_comment( $message->get_comment_data() );

            if ( $comment_id ) {
                $comment_created = $message->update_messages_map( $comment_id );
            }
        }

        return !! $comment_created;
    }

    private function update_comment( $sp_message, $sp_users, $post_id ) {
        $comment_updated = false;

        $message = new SpotIM_Message( 'update', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_updated = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_updated;
    }

    private function delete_comment( $message, $users, $post_id ) {
        $comment_deleted = false;
        $message_deleted_from_map = false;

        $message = new SpotIM_Message( 'delete', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $messages_ids = $message->get_message_and_children_ids_map();

            foreach( $messages_ids as $message_id => $comment_id ) {
                $comment_deleted = wp_delete_comment( $comment_id, true );

                if ( $comment_deleted ) {
                    $message_deleted_from_map = $message->delete_from_messages_map( $message_id );

                    if ( !! $message_deleted_from_map ) {
                        break;
                    }
                } else {
                    break;
                }
            }
        }

        return !! $comment_deleted && !! $message_deleted_from_map;
    }

    private function soft_delete_comment( $sp_message, $sp_users, $post_id ) {
        $comment_soft_deleted = false;

        $message = new SpotIM_Message( 'soft_delete', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_soft_deleted = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_soft_deleted;
    }

    private function anonymous_comment( $sp_message, $sp_users, $post_id ) {
        $comment_anonymized = false;

        $message = new SpotIM_Message( 'anonymous_comment', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_anonymized = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_anonymized;
    }
}