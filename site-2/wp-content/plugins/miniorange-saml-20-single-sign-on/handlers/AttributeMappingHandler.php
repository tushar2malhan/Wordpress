<?php


class AttributeMappingHandler {

	static function clear_attr_list() {
		delete_option(mo_saml_options_test_configuration::TEST_CONFIG_ATTRS);
		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::ATTRIBUTES_CLEARED, 'CLEAR_ATTR_LIST');
		$post_save->post_save_action();
	}
}