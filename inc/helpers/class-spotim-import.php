<?php

define( 'JSONSTUB_EXPORT_URL', 'http://jsonstub.com/export/wordpress/' );

class SpotIM_Import {
    public function __construct( $spot_id ) {
        $this->spot_id = $spot_id;
    }

    public function start() {
        $post_ids = $this->get_post_ids();

        if ( ! empty( $post_ids ) ) {

            while ( ! empty( $post_ids ) ) {
                $post_id = array_shift( $post_ids );
                $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

                $response = $this->request( array(
                    'spot_id' => $this->spot_id,
                    'post_id' => $post_id,
                    'etag' => absint( $post_etag ),
                    'count' => 1000
                ) );

                if ( $response->is_ok && $response->from_etag < $response->new_etag ) {
                    $this->sync_comments( $response->events, $response->users, $post_id );

                    update_post_meta(
                        $post_id,
                        'spotim_etag',
                        absint( $response->new_etag ),
                        $post_etag
                    );
                }
            }
        }

        return 'HODOR';
    }

    private function request( $query_args ) {
        $url = add_query_arg( $query_args, SPOTIM_EXPORT_URL );

        $retrieved_body = wp_remote_retrieve_body(
            wp_remote_get( $url, array( 'sslverify' => true ) )
        );

        $data = json_decode( $retrieved_body );
        $data->is_ok = true;

        return $data;
    }

    private function request_mock( $query_args ) {
        $retrieved_body = wp_remote_retrieve_body(
            wp_remote_get( JSONSTUB_EXPORT_URL, array(
                'headers' => array(
                    'JsonStub-User-Key'     => '0fce8d12-9e2c-45c9-9284-e8c6d57a6fe1',
                    'JsonStub-Project-Key'  => '08e0f77f-5dce-4576-b3b2-4f3ed49c1e67',
                    'Content-Type'          => 'application/json',
                ),
                'body' => json_encode( $query_args )
            ) )
        );

        return $retrieved_body;
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
        if ( ! empty( $events ) ) {
            foreach ( $events as $event ) {
                switch ( $event->type ) {
                    case 'c+':
                    case 'r+':
                        $this->add_new_comment( $event->message, $users, $post_id );
                        break;
                    case 'c~':
                    case 'r~':
                        $this->update_comment( $event->message, $users, $post_id );
                        break;
                    case 'c-':
                    case 'r-':
                        $this->delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c*':
                        $this->soft_delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c@':
                    case 'r@':
                        $this->anonymous_comment( $event->message, $users, $post_id );
                        break;
                }
            }
        }
    }

    private function add_new_comment( $sp_message, $sp_users, $post_id ) {
        $comment_created = false;

        $message = new SpotIM_Message( $sp_message, $sp_users, $post_id );

        if ( ! $message->is_comment_exists() ) {
            $comment_id = wp_insert_comment( $message->get_comment_data() );

            if ( $comment_id ) {
                $message->update_messages_map( $comment_id );

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