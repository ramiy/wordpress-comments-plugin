<?php

class SpotIM_Comment {
    public static function sync( $events, $users, $post_id ) {
        $flag = true;

        if ( ! empty( $events ) ) {
            foreach ( $events as $event ) {

                switch ( $event->type ) {
                    case 'c+':
                    case 'r+':
                        $flag = self::add_new_comment( $event->message, $users, $post_id );
                        break;
                    case 'c~':
                    case 'r~':
                        $flag = self::update_comment( $event->message, $users, $post_id );
                        break;
                    case 'c-':
                    case 'r-':
                        $flag = self::delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c*':
                        $flag = self::soft_delete_comment( $event->message, $users, $post_id );
                        break;
                    case 'c@':
                    case 'r@':
                        $flag = self::anonymous_comment( $event->message, $users, $post_id );
                        break;
                }

                if ( ! $flag ) {
                    break;
                }
            }
        }

        return $flag;
    }

    private static function add_new_comment( $sp_message, $sp_users, $post_id ) {
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

    private static function update_comment( $sp_message, $sp_users, $post_id ) {
        $comment_updated = false;

        $message = new SpotIM_Message( 'update', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_updated = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_updated;
    }

    private static function delete_comment( $message, $users, $post_id ) {
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

    private static function soft_delete_comment( $sp_message, $sp_users, $post_id ) {
        $comment_soft_deleted = false;

        $message = new SpotIM_Message( 'soft_delete', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_soft_deleted = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_soft_deleted;
    }

    private static function anonymous_comment( $sp_message, $sp_users, $post_id ) {
        $comment_anonymized = false;

        $message = new SpotIM_Message( 'anonymous_comment', $sp_message, $sp_users, $post_id );

        if ( $message->is_comment_exists() ) {
            $comment_anonymized = wp_update_comment( $message->get_comment_data() );
        }

        return !! $comment_anonymized;
    }
}