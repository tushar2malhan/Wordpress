<?php


class CustomerLoginHandler {

	public static function register_customer ($post_array, $db_handler) {

		$new_registration = self::get_action_type($post_array);

		if(!self::validate_customer_fields($post_array, $new_registration))
			return;

		$save_array = array();
		$save_array[mo_saml_customer_constants::admin_email] = $new_registration ? sanitize_email($post_array[mo_saml_account_setup_constants::register_email]) : sanitize_email($post_array[mo_saml_account_setup_constants::login_email]);
		$save_array[mo_saml_customer_constants::admin_password] = stripslashes(sanitize_text_field($post_array[mo_saml_account_setup_constants::customer_password]));
		$db_handler->save_options($save_array);

		$customer = new Customersaml();
		$response = '';

		if($new_registration) {
			$content = json_decode($customer->check_customer(), true);
			if (!is_null($content)) {
				if (strcasecmp($content['status'], 'CUSTOMER_NOT_FOUND') == 0)
					$response = self::create_customer($customer, $db_handler);
				else
					$response = self::get_current_customer($customer, $db_handler, $new_registration);
			}
		}
		else {
			$response = self::get_current_customer($customer, $db_handler, $new_registration);
		}
		if (is_array($response) && array_key_exists('status', $response) && $response['status'] == 'success') {
			wp_redirect(admin_url('/admin.php?page=mo_saml_settings&tab=licensing'), 301);
			exit;
		}
	}

	public static function change_account () {
		$class_object = call_user_func( 'mo_saml_customer_constants' . '::getConstants' );
		if (!is_multisite()) {
			//delete all customer related key-value pairs
			foreach($class_object as $key => $value) {
				delete_option($value);
			}
			delete_option('mo_saml_message');

		} else {
			$original_blog_id = get_current_blog_id();
			switch_to_blog($original_blog_id);
			foreach($class_object as $key => $value) {
			    delete_option($value);
			}
			delete_option('mo_saml_message');
		}
	}

	static function get_action_type ($post_array) {
		if(isset($post_array[mo_saml_account_setup_constants::login_email]))
			return false;
		return true;
	}

	static function validate_customer_fields($post_array, $new_registration) {

		if($new_registration) {
			if (Utilities::mo_saml_check_empty_or_null(array($post_array[mo_saml_account_setup_constants::confirm_password]))) {
				$post_save = new PostSaveHandler('ERROR', mo_saml_messages::FIELDS_EMPTY);
			}
			else if (strcmp($post_array[mo_saml_account_setup_constants::customer_password], $post_array[mo_saml_account_setup_constants::confirm_password]) != 0) {
				$post_save = new PostSaveHandler('ERROR', mo_saml_messages::PASSWORD_MISMATCH);
			}
			else if (!filter_var($post_array[mo_saml_account_setup_constants::register_email], FILTER_VALIDATE_EMAIL)) {
				$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CONTACT_EMAIL_INVALID);
			}
		}

		if(Utilities::mo_saml_check_empty_or_null(array($post_array[mo_saml_account_setup_constants::customer_password]))) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::FIELDS_EMPTY);
		}
		else if (self::check_password_pattern(sanitize_text_field($post_array[mo_saml_account_setup_constants::customer_password]))) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::PASSWORD_PATTERN_INVALID);
		}

		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

	static function check_password_pattern($password) {
		$pattern = '/^[(\w)*(\!\@\#\$\%\^\&\*\.\-\_)*]+$/';
		return !preg_match($pattern,$password);
	}

	static function create_customer($customer, $db_handler) {

		$customerKey = json_decode($customer->create_customer(), true);
		if(!is_null($customerKey)){
			$response = array();
			if (strcasecmp($customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0) {
				$api_response = self::get_current_customer($customer, $db_handler, false);
				$response['status'] = $api_response ? "success" : "error";
			} else if (strcasecmp($customerKey['status'],'SUCCESS') == 0) {
				self::update_customer_details($customerKey, $db_handler,true);
				$response['status']="success";
			}

			update_option(mo_saml_customer_constants::admin_password, '');
			return $response;
		}
		return false;
	}

	static function get_current_customer($customer, $db_handler, $new_registration) {

		$content = $customer->get_customer_key();

		if(!is_null($content)) {
			$customerKey = json_decode($content,true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				$error_message = $new_registration ? mo_saml_messages::ACCOUNT_EXISTS : mo_saml_messages::INVALID_CREDENTIALS;
				$post_save =  new PostSaveHandler('ERROR', $error_message);
				$post_save->post_save_action();

				update_option( mo_saml_customer_constants::admin_password, '' );
				$response['status'] = "error";
				return $response;
			}

			self::update_customer_details($customerKey, $db_handler, $new_registration);
			$response['status'] = "success";
			return $response;
		}
		return false;
	}

	static function update_customer_details($customerKey, $db_handler, $new_registration = true) {

		$save_array = array();
		$save_array[mo_saml_customer_constants::customer_key] = $customerKey['id'];
		$save_array[mo_saml_customer_constants::api_key] = $customerKey['apiKey'];
		$save_array[mo_saml_customer_constants::admin_password] = '';

		$certificate = get_option(mo_saml_options_enum_service_providerMoSAML::X509_certificate);

		$db_handler->save_options($save_array);


		$save_message = $new_registration ? mo_saml_messages::REGISTER_SUCCESS : mo_saml_messages::CUSTOMER_FOUND;
		$post_save = new PostSaveHandler('SUCCESS', $save_message);
		$post_save->post_save_action();
	}

}