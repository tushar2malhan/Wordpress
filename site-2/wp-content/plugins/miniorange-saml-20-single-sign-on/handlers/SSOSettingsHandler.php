<?php

class SSOSettingsHandler {

	static public function add_sso_button ($post_array, $db_handler) {

		if(!Utilities::mo_saml_is_sp_configured()) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::SP_NOT_CONFIGURED, 'SERVICE_PROVIDER_NOT_FOUND');
		}
		else {
			$save_array[mo_saml_options_enum_sso_loginMoSAML::SSO_button] = array_key_exists(mo_saml_options_enum_sso_loginMoSAML::SSO_button, $post_array) ? sanitize_text_field($post_array[mo_saml_options_enum_sso_loginMoSAML::SSO_button]) : 'false';
			$db_handler->save_options($save_array);

			$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::SETTINGS_UPDATED, 'SSO_SETTINGS', $save_array);
		}
		$post_save->post_save_action();
	}
}