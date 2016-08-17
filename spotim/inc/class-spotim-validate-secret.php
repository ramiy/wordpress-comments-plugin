<?php

/**
 * User: Ronny
 * Date: 16/08/2016
 * Time: 12:49
 */
class SpotIM_ValidateSecret  extends SpotIM_RestAPI {

	public function validate($options, $secret) {
		if (empty($secret))
			return (object)['success' => 'false', 'error_code' => 'No Secret Code'];

		$connect_data = ['plugin_secret' => $secret,
						 'spot_id' => $options->get('spot_id')];
		return $this->rest_get_data('activate', $connect_data);
	}
}