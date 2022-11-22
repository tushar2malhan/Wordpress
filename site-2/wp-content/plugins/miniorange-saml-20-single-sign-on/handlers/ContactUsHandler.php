<?php


class ContactUsHandler {

	public static function send_contact_us ($post_array, $db_handler) {

		$call_setup = false;
		if(array_key_exists('saml_setup_call',$post_array) === true) {

			if (!self::validate_call_setup_fields($post_array))
				return;

			$time_zone = $post_array[mo_saml_contact_us_constants::Customer_timezone];
			$call_date = $post_array[mo_saml_contact_us_constants::Customer_call_date];
			$call_time = $post_array[mo_saml_contact_us_constants::Customer_call_time];
			$call_setup = true;
		}

		if (!self::validate_contact_us_fields($post_array, $call_setup))
			return;

		$email    = sanitize_email($post_array[mo_saml_contact_us_constants::Customer_email]);
		$query    = sanitize_text_field($post_array[mo_saml_contact_us_constants::Customer_query]);
		$phone    = sanitize_text_field($post_array[mo_saml_contact_us_constants::Customer_phone]);

		$plugin_config = mo_saml_miniorange_import_export(true, true);

		if($call_setup == true) {
			$local_timezone     = 'Asia/Kolkata';
			$call_datetime      = $call_date.$call_time;
			$convert_datetime   = strtotime ($call_datetime);
			$ist_date           = new DateTime(date ( 'Y-m-d H:i:s' , $convert_datetime ), new DateTimeZone($time_zone));

			$ist_date->setTimezone(new DateTimeZone($local_timezone));
			$query = $query . '<br><br>' .'Meeting Details: '.'('.$time_zone.') '. date('d M, Y  H:i',$convert_datetime). ' [IST Time -> '. $ist_date->format('d M, Y  H:i').']'.'<br><br>'.'Plugin Configuration: '.$plugin_config;
		}
		else
			$query = $query.'<br><br>'.'Plugin Configuration: '.$plugin_config;


		$customer = new CustomerSaml();
		$response = $customer->submit_contact_us($email, $phone, $query, $call_setup);

		if(!is_null($response) && $response != false)
			$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::QUERY_SUBMITTED);
		else
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::QUERY_NOT_SUBMITTED);

		$post_save->post_save_action();
	}

	static function validate_contact_us_fields($post_array, $call_setup) {

		$validate_fields_array = array($post_array[mo_saml_contact_us_constants::Customer_email], $post_array[mo_saml_contact_us_constants::Customer_query]);
		if($call_setup == true)
			$validate_fields_array = array($post_array[mo_saml_contact_us_constants::Customer_email]);

		if (Utilities::mo_saml_check_empty_or_null($validate_fields_array)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CONTACT_EMAIL_EMPTY);
		}
		else if (!filter_var($post_array[mo_saml_contact_us_constants::Customer_email], FILTER_VALIDATE_EMAIL)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CONTACT_EMAIL_INVALID);
		}
		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

	static function validate_call_setup_fields($post_array) {

		$validate_fields_array = array($post_array[mo_saml_contact_us_constants::Customer_call_time], $post_array[mo_saml_contact_us_constants::Customer_call_date], $post_array[mo_saml_contact_us_constants::Customer_timezone]);

		if(Utilities::mo_saml_check_empty_or_null($validate_fields_array))
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::CALL_SETUP_DETAILS_EMPTY);

		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

}