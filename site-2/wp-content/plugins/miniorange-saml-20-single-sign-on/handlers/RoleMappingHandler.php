<?php

class RoleMappingHandler {

	static function update_default_role($post_array, $db_handler) {

		$save_array = array();
		if(!Utilities::mo_saml_check_empty_or_null(array($post_array[mo_saml_options_enum_role_mappingMoSAML::Role_default_role])))
			$save_array[mo_saml_options_enum_role_mappingMoSAML::Role_default_role] = sanitize_text_field($post_array[mo_saml_options_enum_role_mappingMoSAML::Role_default_role]);

		$db_handler->save_options($save_array);

		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::UPDATED_DEFAULT_ROLE, 'DEFAULT_ROLE_ID', $save_array);
		$post_save->post_save_action();
	}
}