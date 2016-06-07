<?php

// define( 'JSONSTUB_EXPORT_URL', 'http://jsonstub.com/export/wordpress/anonymous/reply' );

class SpotIM_Import {
    public function __construct( $options ) {
        $this->options = $options;
    }

    public function start() {
        $post_ids = $this->get_post_ids();
        $streams = array();
        $response = array(
            // 'status' => 'error',
            // 'message' => __( 'Something gone wrong, try a bit later or contact us at support@spot.im', 'wp-spotim' )
            'status' => 'success',
            'message' => __( 'Done. You are welcome.', 'wp-spotim' )
        );

        $this->options->reset( 'import_request_error' );
        $this->options->reset( 'import_sync_comments_error' );

        if ( ! empty( $post_ids ) ) {

            // import comments data from Spot.IM
            while ( ! empty( $post_ids ) ) {
                $post_id = array_shift( $post_ids );
                $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

                $streams[] = $this->request( array(
                    'spot_id' => $this->options->get( 'spot_id' ),
                    'post_id' => $post_id,
                    'etag' => absint( $post_etag ),
                    'count' => 1000
                ) );

                // error checking
                $request_error = $this->options->get( 'import_request_error' );

                if ( ! empty( $request_error ) ) {
                    $response['message'] = $this->options->get( 'import_request_error' );
                    break;
                }
            }
        } else {
            $response['status'] = 'success';
            $response['message'] = __( 'Your website doesn\'t have any publish blog posts', 'wp-spotim' );
        }

        // sync comments data with wordpress comments
        if ( ! empty( $streams ) ) {
            while ( ! empty( $streams ) ) {
                $stream = array_shift( $streams );
                $post_etag = get_post_meta( $stream->post_id, 'spotim_etag', true );

                if ( $stream->from_etag < $stream->new_etag ) {

                    $sync_status = $this->sync_comments(
                        $stream->events,
                        $stream->users,
                        $stream->post_id
                    );

                    if ( ! $sync_status ) {
                        $response['status'] = 'error';
                        $response['message'] = __( 'Could not import comments of from this stream: '. json_encode( $stream ), 'wp-spotim' );
                        break;
                        // $request_error = $this->options->get( 'import_sync_comments_error' );
                        // if ( ! empty( $request_error ) ) {
                        //     $response['message'] = $this->options->get( 'import_sync_comments_error' );
                        //     break;
                        // }
                    }

                } else if ( $stream->from_etag === $stream->new_etag ) {
                    update_post_meta(
                        $stream->post_id,
                        'spotim_etag',
                        absint( $stream->new_etag ),
                        $post_etag
                    );
                }
            }
        } else {
            $response['status'] = 'success';
            $response['message'] = __( 'All comments are up to date.', 'wp-spotim' );
        }

        return $response;

        // $response = $this->request_mock();
        // file_put_contents( dirname( __FILE__ )  . '/response.txt', json_encode( $response ) . "\r\n", FILE_APPEND);

        // if ( $response->is_ok && $response->from_etag < $response->new_etag ) {
        //     $this->sync_comments( $response->events, $response->users, $response->post_id );
        // }
    }

    private function request( $query_args ) {
        $url = add_query_arg( $query_args, SPOTIM_EXPORT_URL );
        $response_body = new stdClass();
        $is_ok = false;

        $response = wp_remote_get( $url, array( 'sslverify' => true ) );

        if ( ! is_wp_error( $response ) &&
             'OK' === wp_remote_retrieve_response_message( $response ) &&
             200 === wp_remote_retrieve_response_code( $response ) ) {

            $response_body = json_decode( wp_remote_retrieve_body( $response ) );

            if ( isset( $response_body->success ) && false === $response_body->success ) {
                $is_ok = false;
            } else {
                $is_ok = true;
            }
        }

        if ( ! $is_ok ) {
            $this->options->update( 'import_request_error', 'Retriving data failed from this URL: ' . $url );
        }

        return $response_body;
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

    private function get_post_ids() {
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'post',
            'post_status' => 'publish',
            'fields' => 'ids'
        );

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