<?php

include 'PostSaveHandler.php';

class UploadMetadataHandler {

	private static $metadata_type;

	static function upload_metadata ($post_array, $file_array, $db_handler) {
		if ( !function_exists('wp_handle_upload') ) {
			require_once (ABSPATH . 'wp-admin/includes/file.php');
		}

		$metadata_file_empty        = empty($file_array[mo_saml_options_enum_metadata_uploadMoSAML::Metadata_file]) ? true: false;
		$metadata_file_name_empty   = Utilities::mo_saml_check_empty_or_null(array($file_array[mo_saml_options_enum_metadata_uploadMoSAML::Metadata_file]['tmp_name']));
		$metadata_url_empty         = Utilities::mo_saml_check_empty_or_null(array($post_array[mo_saml_options_enum_metadata_uploadMoSAML::Metadata_URL]));

		if(!self::validate_metadata_fields($post_array, $metadata_file_empty, $metadata_url_empty, $metadata_file_name_empty))
			return;

		self::set_metadata_type($metadata_url_empty);
		$file = self::get_file_contents($post_array, $file_array);

		if(Utilities::mo_saml_check_empty_or_null(array($file))) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_METADATA_CONFIG, 'UPLOAD_METADATA_INVALID_CONFIGURATION');
			$post_save->post_save_action();
			return;
		}

		self::handle_upload_metadata($file, $post_array, $db_handler);
	}

	static function validate_metadata_fields ($post_array, $metadata_file_empty, $metadata_url_empty, $metadata_file_name_empty) {

		if(Utilities::mo_saml_check_empty_or_null(array($post_array[mo_saml_options_enum_metadata_uploadMoSAML::Identity_provider_name])))
			$post_save = new PostSaveHandler( 'ERROR', mo_saml_messages::IDP_NAME_EMPTY, 'IDP_NAME_EMPTY' );

		else if (!preg_match("/^\w*$/", $post_array[mo_saml_options_enum_metadata_uploadMoSAML::Identity_provider_name])) {
			$log_object = [mo_saml_options_enum_metadata_uploadMoSAML::Identity_provider_name => $post_array[mo_saml_options_enum_metadata_uploadMoSAML::Identity_provider_name]];
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_IDP_NAME_FORMAT, 'INVAILD_IDP_NAME_FORMAT', $log_object);
		}
		else if ($metadata_file_empty == 'false' && $metadata_file_name_empty)
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::METADATA_NAME_EMPTY, 'UPLOAD_METADATA_NAME_EMPTY');

		else if ($metadata_file_empty && $metadata_url_empty)
			$post_save = new PostSaveHandler( 'ERROR', mo_saml_messages::METADATA_EMPTY, 'UPLOAD_METADATA_EMPTY');

		if(isset($post_save)) {
			$post_save->post_save_action();
			return false;
		}
		return true;
	}

	static function set_metadata_type ($metadata_url_empty) {
		self::$metadata_type = 'file';
		if (!$metadata_url_empty)
			self::$metadata_type = 'url';
	}

	static function get_file_contents ($post_array, $file_array) {

		if (self::$metadata_type == 'file') {
			$file = Utilities::mo_safe_file_get_contents( $_FILES[mo_saml_options_enum_metadata_uploadMoSAML::Metadata_file]['tmp_name'] );
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UPLOAD_METADATA_SUCCESS'),MoSAMLLogger::DEBUG);
		}
		else {
			$url = filter_var( $post_array[mo_saml_options_enum_metadata_uploadMoSAML::Metadata_URL], FILTER_SANITIZE_URL );
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UPLOAD_METADATA_URL',array('url'=>$url)), MoSAMLLogger::INFO);

			$response = Utilities::mo_saml_wp_remote_get($url, array('sslverify'=>false));
			if(!empty($response) && isset($response)){
				$file = $response['body'];
				MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UPLOAD_METADATA_SUCCESS_FROM_URL'), MoSAMLLogger::INFO);
			}
			else {
				$file = null;
				MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UPLOAD_METADATA_ERROR_FROM_URL'), MoSAMLLogger::ERROR);
			}
		}
		return $file;
	}

	static function handle_upload_metadata ($file, $post_array, $db_handler) {

		$old_error_handler = set_error_handler( array( self::class, 'handleXmlError' ) );
		$document = new DOMDocument();
		$document->loadXML($file);
		restore_error_handler();
		$first_child = $document->firstChild;
		if(Utilities::mo_saml_check_empty_or_null(array($first_child))) {
			self::handle_empty_metadata_child();
			return;
		}

		$metadata           = new IDPMetadataReader($document);
		$identity_providers = $metadata->getIdentityProviders();
		if(Utilities::mo_saml_check_empty_or_null(array($identity_providers))) {
			self::handle_empty_metadata_idp_value();
			return;
		}

		$save_array = array();
		foreach ($identity_providers as $key => $idp) {
			$save_array[mo_saml_options_enum_service_providerMoSAML::Identity_name] = sanitize_text_field($post_array[mo_saml_options_enum_metadata_uploadMoSAML::Identity_provider_name]);
			$save_array[mo_saml_options_enum_service_providerMoSAML::Login_URL] = $idp->getLoginURL('HTTP-Redirect');
			$save_array[mo_saml_options_enum_service_providerMoSAML::Issuer] = $idp->getEntityID();

			//certs already sanitized in Metadata Reader
			$saml_x509_certificate  = $idp->getSigningCertificate();
			$save_array[mo_saml_options_enum_service_providerMoSAML::X509_certificate] = maybe_serialize($saml_x509_certificate);

			$db_handler->save_options($save_array);
			break;
		}
		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::METADATA_UPLOAD_SUCCESS, 'UPLOAD_METADATA_CONFIGURATION_SAVED', $save_array);
		$post_save->post_save_action();
	}

	static function handle_empty_metadata_child() {

		if (self::$metadata_type == 'file')
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_METADATA_FILE, 'UPLOAD_METADATA_INVALID_FILE');
		else
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_METADATA_URL, 'UPLOAD_METADATA_INVALID_URL');

		$post_save->post_save_action();
	}

	static function handle_empty_metadata_idp_value() {

		if (self::$metadata_type == 'file')
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_METADATA_FILE, 'UPLOAD_METADATA_INVALID_FILE');
		else
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::INVALID_METADATA_URL, 'UPLOAD_METADATA_INVALID_URL');

		$post_save->post_save_action();
	}

	static function handleXmlError ($errno, $errstr, $errfile, $errline) {
		if ( $errno == E_WARNING && ( substr_count( $errstr, "DOMDocument::loadXML()" ) > 0 ) ) {
			return true;
		} else {
			return false;
		}
	}

}