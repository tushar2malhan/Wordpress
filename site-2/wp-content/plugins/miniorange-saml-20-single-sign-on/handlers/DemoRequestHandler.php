<?php


class DemoRequestHandler {

	static $status = "";

	static public function request_demo ($post_array) {
		//@TODO: Warning for demo already requested
		$demo_request = array();
		$demo_request[mo_saml_demo_constants::demo_email] = isset($post_array[mo_saml_demo_constants::demo_email]) ? sanitize_email($post_array[mo_saml_demo_constants::demo_email]) : get_option('mo_saml_admin_email');
		$demo_request[mo_saml_demo_constants::demo_plan] = isset($post_array[mo_saml_demo_constants::demo_plan]) ? sanitize_text_field($post_array[mo_saml_demo_constants::demo_plan]) : '';

		if(!self::validate_demo_request_fields($demo_request))
			return;

		$demo_request[mo_saml_demo_constants::demo_description] = sanitize_text_field($post_array[mo_saml_demo_constants::demo_description]);
		$demo_request[mo_saml_demo_constants::demo_addons] = self::get_selected_addons($post_array, mo_saml_options_addons::$ADDON_TITLE);

		if(array_key_exists($demo_request[mo_saml_demo_constants::demo_plan],mo_saml_license_plans::$license_plans_slug))
			self::create_wordpress_demo($demo_request);
		else
			self::$status = __('Please setup manual demo.','miniorange-saml-20-single-sign-on');

		$query = self::set_demo_query($demo_request);
		self::send_demo_request($query);
	}

	static function validate_demo_request_fields ($demo_request) {

		$validate_fields_array = array($demo_request[mo_saml_demo_constants::demo_email], $demo_request[mo_saml_demo_constants::demo_plan]);
		if(Utilities::mo_saml_check_empty_or_null($validate_fields_array)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CONTACT_EMAIL_EMPTY);
			self::$status = __('Error: Email address or Demo plan is Empty','miniorange-saml-20-single-sign-on');
		}
		if (!filter_var($demo_request[mo_saml_demo_constants::demo_email],FILTER_VALIDATE_EMAIL)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CONTACT_EMAIL_INVALID);
		}
		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

	static function get_selected_addons ($post_array, $addons) {
		$addons_selected = array();
		foreach($addons as $key => $value){
			if(isset($post_array[$key]) && $post_array[$key] == "true")
				$addons_selected[$key] = $value;
		}
		return $addons_selected;
	}

	static function create_wordpress_demo ($demo_request) {

		$plugin_version = mo_saml_license_plans::$license_plans_slug[$demo_request[mo_saml_demo_constants::demo_plan]];
		$headers = array('Content-Type' => 'application/x-www-form-urlencoded', 'charset' => 'UTF - 8');
		$args = array(
			'method' =>'POST',
			'body' => array(
				'option' => 'mo_auto_create_demosite',
				'mo_auto_create_demosite_email' => $demo_request[mo_saml_demo_constants::demo_email],
				'mo_auto_create_demosite_usecase' => $demo_request[mo_saml_demo_constants::demo_description],
				'mo_auto_create_demosite_demo_plan' => $plugin_version,
			),
			'timeout' => '20',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
		);

		$response = wp_remote_post( mo_saml_demo_constants::demo_site_url, $args );
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			echo "Something went wrong:".esc_html($error_message);
			exit();
		}
		$output = wp_remote_retrieve_body($response);
		$output = json_decode($output);

		if(is_null($output)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::DEMO_REQUEST_FAILED);
			self::$status = __('Error: Something went wrong while setting up demo.','miniorange-saml-20-single-sign-on');
		}
		else if ($output->status !== 'SUCCESS') {
			$post_save = new PostSaveHandler('ERROR', $output->message);
			self::$status = __('Error :','miniorange-saml-20-single-sign-on') .$output->message;
		}
		else {
			$post_save = new PostSaveHandler('SUCCESS', $output->message);
			self::$status = __('Success :','miniorange-saml-20-single-sign-on').$output->message;
		}

		if(isset($post_save))
			$post_save->post_save_action();
	}

	static function set_demo_query ($demo_request) {

		$plan_name = mo_saml_license_plans::$license_plans[$demo_request[mo_saml_demo_constants::demo_plan]];

		$message = "[Demo For Customer] : " . $demo_request[mo_saml_demo_constants::demo_email];
		$message .= " <br>[Selected Plan] : " . $plan_name;

		if(!empty($demo_request[mo_saml_demo_constants::demo_description]))
			$message .= " <br>[Requirements] : " . $demo_request[mo_saml_demo_constants::demo_description];

		$message .= " <br>[Status] : " .self::$status;

		if(!empty($demo_request[mo_saml_demo_constants::demo_addons])){
			$message .= " <br>[Addons] : ";
			foreach($demo_request[mo_saml_demo_constants::demo_addons] as $key => $value){
				$message .= $value;
				if(next($demo_request[mo_saml_demo_constants::demo_addons]))
					$message .= ", ";
			}
		}
		return $message;
	}

	static function send_demo_request ($query) {

		$user = wp_get_current_user();
		$customer = new Customersaml();
		$email = empty(get_option("mo_saml_admin_email")) ? $email = $user->user_email : get_option("mo_saml_admin_email");
		$phone = get_option( 'mo_saml_admin_phone' );
		$demo_status = strpos(self::$status,"Error");

		$response = json_decode( $customer->send_email_alert( $email, $phone, $query, true ), true );

		if (is_array($response) && array_key_exists('status', $response) && $response['status'] == 'ERROR') {
			$post_save = new PostSaveHandler('ERROR', $response['message']);
		}
		else if ($response == false || $demo_status !== false) {
			$post_save = new PostSaveHandler('ERROR', self::$status);
		}
		else if (json_last_error() == JSON_ERROR_NONE) {
			$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::QUERY_SUBMITTED);
		}

		if(isset($post_save))
			$post_save->post_save_action();
	}

}