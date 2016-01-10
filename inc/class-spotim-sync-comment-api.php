<?php

class SpotIM_Sync_Comment_API {

    public function __construct() {
        
    }

    public function run_sync_comment($json_feed) {
        $feed = json_decode($json_feed);
        $count = 0;
        if ((isset($feed->from_etag) && isset($feed->new_etag)) && ($feed->new_etag != $feed->from_etag)) {
            $post_id = $feed->post_id;
            update_post_meta($post_id, 'etag', $feed->new_etag);
            if (isset($feed->events) && count($feed->events) > 0):
                foreach ($feed->events as $event):
                    $flag = false;
                    switch ($event->type):
                        case "c+":
                            $flag = $this->addNewComment($event->message, $feed->users, $post_id);
                            break;
                        case "r+":
                            $flag = $this->addNewComment($event->message, $feed->users, $post_id);
                            break;
                        case "c~":
                            $flag = $this->updateComment($event->message, $post_id);
                            break;
                        case "r~":
                            $flag = $this->updateComment($event->message, $post_id);
                            break;
                        case "c-":
                            $flag = $this->deleteComment($event->message, $post_id);
                            break;
                        case "r-":
                            $flag = $this->deleteComment($event->message, $post_id);
                            break;
                        case "c*":
                            $flag = $this->deleteCommentSoft($event->message, $post_id);
                            break;
                        case "r*":
                            $flag = $this->deleteCommentSoft($event->message, $post_id);
                            break;
                    endswitch;
                    if ($flag)
                        $count++;
                endforeach;
            endif;
        }
        return $count;
    }

    private function addNewComment($message, $users, $post_id) {
        $comment_date = date("Y-m-d H:i:s", $message->written_at);
        $comment_parent = ((isset($message->comment_id)) ? $message->comment_id : 0);
        $data = array(
            'comment_post_ID' => $post_id,
            'comment_author' => (isset($users->{$message->user_id}->user_name) ? $users->{$message->user_id}->user_name : 'Guest'),
            'comment_author_email' => (isset($users->{$message->user_id}->email) ? $users->{$message->user_id}->email : ''),
            'comment_content' => $message->content,
            'comment_date_gmt' => $comment_date,
            'comment_date' => $comment_date,
            'comment_parent' => $comment_parent,
            'comment_approved' => 1,
            'comment_author_IP' => '',
            'comment_agent' => ''
        );
        if ($this->wp_allow_comment($data)) {
            $comment_id = wp_new_comment($data);
            if ($comment_id) {
                $this->addSpotimUser($users, $message);
                $this->addSpotimComment($message, $comment_id);
                return true;
            }
        }
        return false;
    }

    private function updateComment($message, $post_id) {
        global $wpdb;
        $spotim_comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->spotim_messages_table;
        $comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->comment_table;
        $parent_message_id = ((isset($message->comment_id)) ? $message->comment_id : 0);
        $comment_id = $wpdb->get_var("SELECT comment_id FROM $spotim_comment_table WHERE message_id = '$message->id' AND parent_message_id = '$parent_message_id'");
        if ($comment_id) {
            $wpdb->update(
                    $comment_table, array(
                'comment_content' => $message->content
                    ), array('comment_ID' => $comment_id, 'comment_post_ID' => $post_id), array(
                '%s'
                    ), array('%d', '%d')
            );
        }
        return true;
    }

    private function deleteComment($message, $post_id) {
        global $wpdb;
        $spotim_comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->spotim_messages_table;
        $comments = $wpdb->get_results("SELECT * FROM $spotim_comment_table WHERE message_id = '$message->id' OR parent_message_id = '$message->id'");
        if ($comments && $post_id) {
            foreach ($comments as $comment) {
                $wpdb->delete(
                        $spotim_comment_table, array(
                    'message_id' => $comment->message_id,
                    'parent_message_id' => $comment->parent_message_id,
                    'comment_id' => $comment->comment_id
                        ), array('%s', '%s', '%d')
                );
                wp_delete_comment($comment->comment_id, true);
            }
        }
        return true;
    }

    private function deleteCommentSoft($message, $post_id) {
        global $wpdb;
        $spotim_comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->spotim_messages_table;
        $comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->comment_table;
        $comments = $wpdb->get_results("SELECT * FROM $spotim_comment_table WHERE message_id = '$message->id'");
        if ($comments && $post_id) {
            foreach ($comments as $comment) {
                $wpdb->update(
                        $comment_table, array(
                    'comment_content' => $message->content
                        ), array('comment_ID' => $comment->comment_id, 'comment_post_ID' => $post_id), array(
                    '%s'
                        ), array('%d', '%d')
                );
            }
        }
        return true;
    }

    private function addSpotimUser($users, $message) {
        if (isset($message->anonymous) && $message->anonymous == true)
            return FALSE;
        $user_id = $message->user_id;
        $user = $users->{$user_id};
        if (isset($user->email) && $user->email != '') {
            global $wpdb;
            $spotim_users_table = $wpdb->prefix . WP_SpotIM::instance()->admin->spotim_users_table;
            $count = $wpdb->get_var("SELECT COUNT(user_id) FROM $spotim_users_table WHERE user_id = '$user_id'");
            if ($count == 0) {
                $wpdb->insert(
                        $spotim_users_table, array(
                    'user_id' => $user_id,
                    'user_name' => $user->user_name,
                    'display_name' => $user->display_name,
                    'email' => $user->email
                        ), array('%s', '%s', '%s', '%s')
                );
            } else if ($count == 1) {
                $wpdb->update(
                        $spotim_users_table, array(
                    'display_name' => $user->display_name,
                    'email' => $user->email
                        ), array('user_id' => $user_id), array('%s', '%s'), array('%s')
                );
            }
        }
    }

    private function addSpotimComment($message, $comment_id) {
        global $wpdb;
        $spotim_comment_table = $wpdb->prefix . WP_SpotIM::instance()->admin->spotim_messages_table;
        $parent_message_id = ((isset($message->comment_id)) ? $message->comment_id : 0);
        $wpdb->insert(
                $spotim_comment_table, array(
            'message_id' => $message->id,
            'parent_message_id' => $parent_message_id,
            'comment_id' => $comment_id,
            'author_id' => (isset($message->user_id) ? $message->user_id : '')
                ), array('%s', '%s', '%d', '%s')
        );
    }

    private function wp_allow_comment($commentdata) {
        global $wpdb;

        // Simple duplicate check
        // expected_slashed ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
        $dupe = $wpdb->prepare(
                "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ", wp_unslash($commentdata['comment_post_ID']), wp_unslash($commentdata['comment_parent']), wp_unslash($commentdata['comment_author'])
        );
        if ($commentdata['comment_author_email']) {
            $dupe .= $wpdb->prepare(
                    "OR comment_author_email = %s ", wp_unslash($commentdata['comment_author_email'])
            );
        }
        $dupe .= $wpdb->prepare(
                ") AND comment_content = %s LIMIT 1", wp_unslash($commentdata['comment_content'])
        );

        $dupe_id = $wpdb->get_var($dupe);
        if ($dupe_id)
            return FALSE;
        else
            return TRUE;
    }

}
