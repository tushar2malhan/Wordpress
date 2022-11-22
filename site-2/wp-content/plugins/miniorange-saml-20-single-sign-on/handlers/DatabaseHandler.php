<?php

class DatabaseHandler {

	private $optionHandlerType;

	function __construct() {
		$this->optionHandlerType = 'DB_OPTION';
	}

	public function save_options($save_array) {
		if(!empty($save_array)) {
			foreach ($save_array as $key => $value) {
				update_option($key, $value);
			}
		}
	}
}