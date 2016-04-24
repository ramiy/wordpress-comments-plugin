<?php

class SpotIM_Export {
    public function __construct() {}

    public static function start_json( $post_ids = false ) {
        $conversations_bucket = array();

        foreach ( (array) $post_ids as $post_id ) {
            $exporter_instance = new SpotIM_JSON_Conversation( $post_id );
            $post_result = $exporter_instance->export();

            // if post was successfully processed, and guaranteed to have comments
            if ( $post_result && ! $exporter_instance->is_empty() ) {
                $conversations_bucket['conversation'] = $post_result;
            }
        }

        return $conversations_bucket;
    }

    public static function generate_json_by_post( $post_ids ) {
        $result = array();
        $result = self::start_json( $post_ids );

        $filename = apply_filters( 'spotim_json_download_filename', sprintf( 'spotim-export-%s.json', date_i18n( 'd-m-Y_h-i', time() ) ) );

        echo json_encode( $result );
        die;
    }

}
