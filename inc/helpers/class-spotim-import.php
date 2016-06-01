<?php


class SpotIM_Import {
    public function __construct( $spot_id ) {
        $this->spot_id = $spot_id;
    }

    public function start() {
        $post_ids = $this->get_post_ids();
        // $responses = array();

        if ( ! empty( $post_ids ) ) {

            while ( ! empty( $post_ids ) ) {
                $post_id = array_shift( $post_ids );

                $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

                $url = add_query_arg( array(
                    'spot_id' => $this->spot_id,
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

    private function get_post_ids() {
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'post',
            'post_status' => 'publish',
            'fields' => 'ids'
        );

        return get_posts( $args );
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

    private function add_new_comment( $sp_message, $sp_users, $post_id ) {
        $comment_created = false;

        $message = new SpotIM_Message( $sp_message, $sp_users, $post_id );

        if ( ! $message->is_comment_exists() ) {
            $comment_id = wp_insert_comment( $message->get_comment_data() );

            if ( $comment_id ) {
                // $message->update_messages_map( $comment_id );

                $comment_created = true;
            }
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