<?php

class SpotIM_RestAPI {

    protected function rest_get_data( $subject, $args = [] ) {
        return $this->rest_get_data_url(SPOT_IM_REST_API, $subject, $args);
    }

    protected function rest_get_data_url($url, $subject, $args = [] ) {

        if (count($args) == 0 || !array_key_exists('headers', $args))
            $args['headers'] = [ "Content-type" => "application/json" ];
        else if ( !array_key_exists('Content-type', $args['headers']) )
            $args['headers'][ "Content-type"] = "application/json";

        $params = [
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => [],
                'body' => $args,
                'cookies' => []
            ];
        $response = wp_remote_post( $url.$subject, $params );
        if ( is_wp_error( $response ) )
            return (object)['success' => 'false', 'error_code' => 'WP:could not connect'];
        if ( $response['response']['code'] != 200)
            return (object)['success' => 'false', 'error_code' => "WP:responce({$response['response']['code']}):{$response['response']['message']} - $url$subject"];

        $body = wp_remote_retrieve_body( $response );

        if (is_wp_error( $body ))
            return (object)['success' => 'false', 'error_code' => 'WP:no body'];

        $response = json_decode( $body );
        if ( isset($response->code) && $response->code != 200)
            return (object)['success' => 'false', 'error_code' => "WP:responce({$response->code}):{$response->message} - $url$subject"];

        return $response;
    }
}
