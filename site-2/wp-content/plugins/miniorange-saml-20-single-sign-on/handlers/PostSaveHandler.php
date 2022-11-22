<?php


class PostSaveHandler {

	private $status;
	private $status_message;
	private $log_object;
	private $log_message;
	private $log_level;
	private $log_entry = true;

	function __construct($status, $status_message, $log_message = '', $log_object = '') {

		$this->status = $status;
		$this->status_message = $status_message;
		$this->log_message = $log_message;
		$this->log_level = MoSAMLLogger::DEBUG;

		if(empty($log_message))
			$this->log_entry = false;
		else {
			if($status == 'ERROR')
				$this->log_level = MoSAMLLogger::ERROR;

			if(!empty($log_object))
				$this->log_object = $log_object;
		}
	}

	function post_save_action() {
		update_option( 'mo_saml_message', __($this->status_message,'miniorange-saml-20-single-sign-on'));
		if($this->status == 'ERROR')
			Utilities::mo_saml_show_error_message();
		else
			Utilities::mo_saml_show_success_message();

		if($this->log_entry == true)
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage($this->log_message, $this->log_object),$this->log_level);
	}

}