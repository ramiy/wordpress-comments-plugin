<?php
/**
 * Created by Ronny Sherer
 * Date: 07/08/2016
 * Time: 15:17
 */

class SpotIM_posttypes
{
	private static $instance;
	public $types;

	private function __construct() {
		$this->types = [];
		$args = array(
			'public'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'  => true,
			'_builtin' => false
		);

		foreach (get_post_types( $args, 'objects' ) as $posttypeObj) {
			if (post_type_supports($posttypeObj->name, 'comments')) {
				$this->types[] = (object)[
						'name' => $posttypeObj->labels->name,
						'slug' => $posttypeObj->name,
						'option' => "enable_comments_on_{$posttypeObj->name}"
					];
			}
		}
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new SpotIM_posttypes();
		}

		return self::$instance;
	}

	public function count() {
		return count( $this->types );
	}

	public function get($findtype) {
		if (is_numeric($findtype) && intval($findtype) < count( $this->types ) && intval($findtype)  >= 0 ) {
			return $this->types[intval($findtype)];
		}
		else {
			foreach ($this->types as $type) {
				if ($findtype == $type->slug) {
					return $type;
				}
			}
		}
		return false;
	}
}