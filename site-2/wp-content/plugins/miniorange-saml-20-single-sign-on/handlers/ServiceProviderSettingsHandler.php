<?php

class ServiceProviderSettingsHandler {

	static function service_provider_save_settings($post_array, $db_handler) {

		if (!self::validate_service_provider_fields($post_array))
			return;

		$save_array = array();
		$save_array[mo_saml_options_enum_service_providerMoSAML::Identity_name] = sanitize_text_field(trim( $post_array[mo_saml_options_enum_service_providerMoSAML::Identity_name] ));
		$save_array[mo_saml_options_enum_service_providerMoSAML::Login_URL] = sanitize_text_field(trim( $post_array[mo_saml_options_enum_service_providerMoSAML::Login_URL] ));
		$save_array[mo_saml_options_enum_service_providerMoSAML::Issuer] = sanitize_text_field(trim( $post_array[mo_saml_options_enum_service_providerMoSAML::Issuer] ));
		$save_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate] = $post_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate];
		$save_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate] = self::parse_saml_certificates($save_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate]);

		if (!$save_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate])
			return;

		if(array_key_exists(mo_saml_options_enum_service_providerMoSAML::Identity_provider_name,$post_array))
			$save_array[mo_saml_options_enum_service_providerMoSAML::Identity_provider_name] = sanitize_text_field($post_array[mo_saml_options_enum_service_providerMoSAML::Identity_provider_name]);

		if(array_key_exists(mo_saml_options_enum_service_providerMoSAML::Is_encoding_enabled,$post_array))
			$save_array[mo_saml_options_enum_service_providerMoSAML::Is_encoding_enabled] = 'checked';
		else 
			$save_array[mo_saml_options_enum_service_providerMoSAML::Is_encoding_enabled] = '';

		$db_handler->save_options($save_array);
		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::IDP_DETAILS_SUCCESS, 'SERVICE_PROVIDER_CONF', $save_array);
		$post_save->post_save_action();
	}

	static function parse_saml_certificates($saml_x509_certificate) {
		foreach ($saml_x509_certificate as $key => $value) {
			if (empty($value)) {
				unset($saml_x509_certificate[$key]);
			} else {
				$saml_x509_certificate[$key] = Utilities::sanitize_certificate($value);

				if ( !@openssl_x509_read($saml_x509_certificate[$key]) ) {
					$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_CERT, 'INVALID_CERT');
					$post_save->post_save_action();
					return false;
				}
			}
		}
		$saml_x509_certificate = maybe_serialize($saml_x509_certificate);

		return $saml_x509_certificate;
	}

	static function validate_service_provider_fields($post_array) {

		$validate_fields_array = array($post_array[mo_saml_options_enum_service_providerMoSAML::Identity_name], $post_array[mo_saml_options_enum_service_providerMoSAML::Login_URL], $post_array[mo_saml_options_enum_service_providerMoSAML::Issuer], $post_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate]);
		if (Utilities::mo_saml_check_empty_or_null($validate_fields_array)) {
			$log_object = [mo_saml_options_enum_service_providerMoSAML::Identity_name => $post_array[mo_saml_options_enum_service_providerMoSAML::Identity_name], mo_saml_options_enum_service_providerMoSAML::Login_URL => $post_array[mo_saml_options_enum_service_providerMoSAML::Login_URL], mo_saml_options_enum_service_providerMoSAML::Issuer => $post_array[mo_saml_options_enum_service_providerMoSAML::Issuer]];
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::FIELDS_EMPTY, 'INVALID_CONFIGURATION_SETTING', $log_object);
		}
		else if ( !preg_match("/^\w*$/", $post_array[mo_saml_options_enum_service_providerMoSAML::Identity_name]) ) {
			$log_object = [mo_saml_options_enum_service_providerMoSAML::Identity_name => $post_array[mo_saml_options_enum_service_providerMoSAML::Identity_name]];
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_FORMAT, 'INVALID_IDP_NAME_FORMAT', $log_object);
		}
		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

}