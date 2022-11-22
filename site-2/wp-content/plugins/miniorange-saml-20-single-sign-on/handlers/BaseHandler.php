<?php

include_once dirname( __FILE__ ) . '/ServiceProviderSettingsHandler.php';
include_once dirname( __FILE__ ) . '/DatabaseHandler.php';
include_once dirname( __FILE__ ) . '/UploadMetadataHandler.php';
include_once dirname( __FILE__ ) . '/ServiceProviderMetadataHandler.php';
include_once dirname( __FILE__ ) . '/ContactUsHandler.php';
include_once dirname( __FILE__ ) . '/AttributeMappingHandler.php';
include_once dirname( __FILE__ ) . '/RoleMappingHandler.php';
include_once dirname( __FILE__ ) . '/SSOSettingsHandler.php';
include_once dirname( __FILE__ ) . '/DemoRequestHandler.php';
include_once dirname( __FILE__ ) . '/CustomerLoginHandler.php';
include_once dirname( __FILE__ ) . '/DebugLogHandler.php';
include_once dirname( __FILE__ ) . '/FeedbackFormHandler.php';

class BaseHandler {

	static function mo_save_settings_handler() {

		if(!Utilities::mo_saml_is_plugin_page(sanitize_text_field($_SERVER['QUERY_STRING'])))
			return;

		if(!current_user_can('manage_options'))
			wp_die("You do not have permission to view this page");

		$db_handler = new DatabaseHandler();

		$option = '';
		if(isset($_POST['option'])) {
			$option = sanitize_text_field($_POST['option']);
			check_admin_referer($option);
		}
		
		switch ($option) {
			case 'login_widget_saml_save_settings':
				ServiceProviderSettingsHandler::service_provider_save_settings($_POST, $db_handler);
				break;
			case 'saml_upload_metadata':
				UploadMetadataHandler::upload_metadata($_POST, $_FILES, $db_handler);
				break;
			case 'mosaml_metadata_download':
				ServiceProviderMetadataHandler::download_plugin_metadata(true);
				break;
			case 'mo_saml_update_idp_settings_option':
				ServiceProviderMetadataHandler::update_sp_endpoints($_POST, $db_handler);
				break;
			case 'mo_saml_contact_us_query_option':
				ContactUsHandler::send_contact_us($_POST, $db_handler);
				break;
			case 'clear_attrs_list':
				AttributeMappingHandler::clear_attr_list();
				break;
			case 'login_widget_saml_role_mapping':
				RoleMappingHandler::update_default_role($_POST, $db_handler);
				break;
			case 'mo_saml_add_sso_button_wp_option':
				SSOSettingsHandler::add_sso_button($_POST, $db_handler);
				break;
			case 'mo_saml_demo_request_option':
				DemoRequestHandler::request_demo($_POST);
				break;
			case 'mo_saml_register_customer':
				CustomerLoginHandler::register_customer($_POST, $db_handler);
				break;
			case 'change_miniorange':
				CustomerLoginHandler::change_account();
				break;
			case 'mo_saml_logger':
				DebugLogHandler::process_logging($_POST);
				break;
			case 'mo_skip_feedback':
				FeedbackFormHandler::skip_feedback();
				break;
			case 'mo_feedback':
				FeedbackFormHandler::send_feedback($_POST);
				break;
		}

	}
}