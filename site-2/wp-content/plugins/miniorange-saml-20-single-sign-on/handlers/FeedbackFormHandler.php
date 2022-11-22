<?php


class FeedbackFormHandler {

	static function skip_feedback() {
		deactivate_plugins(dirname((__DIR__)). '\login.php') ;
		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::PLUGIN_DEACTIVATED);
		$post_save->post_save_action();
	}

	static function send_feedback($post_array) {

		$email = self::get_user_email($post_array);
		$message = self::get_feedback_message($post_array);
		$phone = get_option(mo_saml_customer_constants::admin_phone);
		$customer = new Customersaml();

		$response = json_decode($customer->send_email_alert($email, $phone, $message),true);

		deactivate_plugins(dirname((__DIR__)). '\login.php') ;

		if(!self::validate_response($response))
			return;

		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::FEEDBACK_SUCCESS);
		$post_save->post_save_action();
	}

	static function get_feedback_message($post_array) {
		$message = 'Plugin Deactivated';
		$rate_value = isset($post_array['rate']) ? $post_array['rate'] : '';
		$deactivate_reason_message = isset($post_array['query_feedback']) ? sanitize_text_field($post_array['query_feedback']) : false;
		$reply_required = isset($post_array['get_reply']) ? sanitize_text_field($post_array['get_reply']) : '';
		$multisite_enabled = is_multisite() ? 'True' : 'False';

		$message .= empty($reply_required) ? '<b style="color:red;"> &nbsp; [Reply : don\'t reply]</b>' : '[Reply : yes]';
		$message .= ', [Multisite enabled: ' . $multisite_enabled .']';
		$message .= ', Feedback : '.$deactivate_reason_message.'';
		$message .= ', [Rating :'.$rate_value.']';

		return $message;
	}

	static function get_user_email($post_array) {
		if(isset($post_array['query_mail']) && filter_var($post_array['query_mail'], FILTER_VALIDATE_EMAIL)) {
			$email = $post_array['query_mail'];
		}
		else {
			$email = get_option(mo_saml_customer_constants::admin_email);
			if(empty($email)) {
				$user = wp_get_current_user();
				$email = $user->user_email;
			}
		}
		return $email;
	}

	static function validate_response($response) {
		if (json_last_error() == JSON_ERROR_NONE) {
			if (is_array($response) && array_key_exists('status', $response) && $response['status'] == 'ERROR')
				$post_save = new PostSaveHandler('ERROR', $response['message']);
			else if ($response == false)
				$post_save = new PostSaveHandler('ERROR', mo_saml_messages::QUERY_NOT_SUBMITTED);
		}
		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}
}