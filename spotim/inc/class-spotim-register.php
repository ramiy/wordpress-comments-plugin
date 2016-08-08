<?php
require_once 'class-spotim-rest-api.php';

class SpotIM_Register extends SpotIM_RestAPI {
    private $options, $spot_id;

    public function __construct() {
        $this->options = SpotIM_Options::get_instance();
        $this->spot_id = $this->options->get( 'spot_id' );
    }

    public function register($redirect = false) {
        if (!empty($this->spot_id))
            return false;

        $user = $this->get_admin();
        $name = get_bloginfo('name');
        if (empty($name))
            $name = get_bloginfo('description');

        $creation_data = ['name' => $name, 'website_url' => get_site_url(), 'admin_email' => $user->email];
        $result = $this->rest_get_data('spot', $creation_data);

        if ($result->success != 'true')
        {
            $this->options->update( 'error', $result->error_code );
            return '';
        }
        $this->options->update( 'error', '' );
        $this->options->update( 'spot_id', $this->spot_id = $result->spot_id );
        $this->options->update( 'plugin_secret', $result->plugin_secret );

        /// TODO: Remove line
//      $this->options->update( 'admin_notices', "Spot ID: {$result->spot_id}" );

        if ($redirect) {
            WP_SpotIM::get_instance()->redirect_to_settings();
        }
        return true;
    }

    private function get_admin() {
        $user_query = new WP_User_Query( [ 'role' => 'Administrator', 'orderby' => 'ID' ] );
        $user = $user_query->results[0];

        return (object)['ID'    => $user->data->ID, 'email' => $user->data->user_email];
    }
}
