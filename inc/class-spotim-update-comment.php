<?php

class SpotIM_UpdateComment {
    public $spot_id;
    public $spot_token;
    public $offset = 5;

    public function __construct() {
        add_action( 'wp_ajax_spotim_update_comment', array( $this, 'handle_update_comment' ) );
        add_action( 'spotim_cron_event', array( $this, 'handle_update_comment_cron' ) );
        add_action( 'wp', array( $this, 'spotim_cron_activation' ) );
        add_filter( 'cron_schedules', array( $this, 'add_new_cron_intervals' ) );
    }

    public function handle_update_comment() {
        $this->spot_id = WP_SpotIM::instance()->admin->get_option( 'spot_id', 'sp_foo' );
        $this->spot_token = WP_SpotIM::instance()->admin->get_option( 'spot_token', 'sp_foo' );
        $result = self::run_update_comment();
    }

    private function run_update_comment() {
        $postData = $_POST;
        $feed = $this->get_comment_feed( $this->offset, $postData['offset'] );
        $inserted = $postData['inserted'];

        if ( isset( $feed->folders[0]->messages ) && count( $feed->folders[0]->messages ) > 0 &&
            isset( $feed->total_items->approved ) ) {

            $total_items = $feed->total_items->approved;
            $messages = $feed->folders[0]->messages;

            foreach ( $messages as $message ) {
                $user_display_name = $this->get_user_display_name_by_id(
                    $message->user_id, $feed->folders[0]->users
                );

                $message->display_name = $user_display_name;

                if ( true == $this->insertComment( $message ) ) {
                    $inserted = ( $inserted + 1 );
                }
            }

            $offset = $postData['offset'] + $this->offset;

            if ( ( $total_items - 1 ) <= $offset ) {
                echo json_encode(array(
                    'hasMore' => false,
                    'inserted' => $inserted
                ));
            } else {
                echo json_encode(array(
                    'hasMore' => true,
                    'inserted' => $inserted,
                    'offset' => $offset
                ));
            }
        }

        die;
    }

    private function insertComment( $message ) {
        global $wpdb;

        $comment_table = $wpdb->prefix . "comments";
        $post_table = $wpdb->prefix . "posts";
        $comment_date = date( "Y-m-d H:i:s", $message->timestamp );
        $post_id = url_to_postid( $message->conversation_url );
        $comment_id = $wpdb->get_var("
            SELECT comment_ID
            FROM `$comment_table`
            WHERE comment_post_ID = $post_id
            AND comment_date = '$comment_date'
            LIMIT 1
        ");

        if ( ! $comment_id ) {
            $post = $wpdb->get_var("
                SELECT ID
                FROM `$post_table`
                WHERE ID = $post_id
                LIMIT 1
            ");

            if ( $post ) {
                $data = array(
                    'comment_post_ID' => $post_id,
                    'comment_author' => $message->display_name,
                    'comment_content' => $message->content[0]->text,
                    'comment_type' => $message->content[0]->type,
                    'comment_date_gmt' => $comment_date,
                    'comment_date' => $comment_date,
                    'comment_approved' => 1,
                    'comment_author_IP' => '',
                    'comment_agent' => ''
                );

                $new_comment_id = wp_new_comment( $data );

                if ( $new_comment_id ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function get_comment_feed( $count = 0, $offset = 0 ) {
        $url = "https://www.spot.im/api/moderation/folder_messages/approved?spot_id=$this->spot_id&sort_by=newest&count=$count&offset=$offset";
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        $headers = array();
        $headers[] = "X-Spotim-Token: $this->spot_token";
        $headers[] = "Content-Type: application/json";
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $server_output = curl_exec( $ch );
        curl_close( $ch );

        return json_decode( $server_output );
    }

    public function add_new_cron_intervals( $schedules ) {
        $cron_time = WP_SpotIM::instance()->admin->get_option( 'spot_cronjob_frequency', 'sp_foo' );

        if ( empty( $cron_time ) ) {
            $cron_time = 1800;
        }

        $schedules['spotim_time'] = array(
            'interval' => $cron_time
        );

        return $schedules;
    }

    public function spotim_cron_activation() {
        if ( ! wp_next_scheduled( 'spotim_cron_event' ) ) {
            wp_schedule_event( current_time( 'timestamp' ), 'spotim_time', 'spotim_cron_event' );
        }
    }

    public function handle_update_comment_cron() {
        $this->spot_id = WP_SpotIM::instance()->admin->get_option( 'spot_id', 'sp_foo' );
        $this->spot_token = WP_SpotIM::instance()->admin->get_option( 'spot_token', 'sp_foo' );

        self::run_update_comment_cron();
    }

    private function run_update_comment_cron() {
        $feedHack = $this->get_comment_feed( 1, 0 );

        if ( isset( $feedHack->total_items->approved ) && $feedHack->total_items->approved > 0 ) {
            $offset = 0;

            while ( $feedHack->total_items->approved >= $offset ) {
                $feed = $this->get_comment_feed( 10, $offset );

                if ( isset( $feed->folders[0]->messages ) && count( $feed->folders[0]->messages ) > 0 &&
                    isset( $feed->total_items->approved ) ) {
                    $messages = $feed->folders[0]->messages;

                    foreach ( $messages as $message ) {
                        $user_display_name = $this->get_user_display_name_by_id(
                            $message->user_id, $feed->folders[0]->users
                        );
                        $message->display_name = $user_display_name;
                        $this->insertComment( $message );
                    }
                }
                $offset = $offset + 10;
            }
        }

        die;
    }

    private function get_user_display_name_by_id( $user_id, $users ) {
        $output = $user_id;

        if ( $user_id && $users ) {
            foreach ( $users as $user ) {
                if ( $user->id == $user_id ) {
                    if ( ! empty( $user->display_name ) ) {
                        $output = $user->display_name;
                    } elseif ( ! empty( $user->user_name ) ) {
                        $output = $user->user_name;
                    }
                }
            }
        }

        return $output;
    }
}

new SpotIM_UpdateComment();
