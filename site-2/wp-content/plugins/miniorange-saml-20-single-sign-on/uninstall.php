<?php

if ( !defined('WP_UNINSTALL_PLUGIN') )
    exit();

require_once dirname(__FILE__) . '/includes/lib/mo-saml-options-enum.php';
if( !(get_option('mo_saml_keep_settings_on_deletion')==='true') ) {

    if (!is_multisite()) {
        // delete all stored key-value pairs
       mo_saml_delete_configuration_array();
    } else {
        $original_blog_id = get_current_blog_id();
        switch_to_blog($original_blog_id);
        mo_saml_delete_configuration_array();
    }
}

function mo_saml_delete_configuration_array() {
    $tab_class_names_array =array(
        'mo_saml_options_test_configuration',
        'mo_saml_customer_constants',
        'mo_saml_settings_constants',
        'mo_saml_options_enum_service_providerMoSAML',
        'mo_saml_options_enum_attribute_mappingMoSAML',
        'mo_saml_options_enum_role_mappingMoSAML'
    );
    foreach($tab_class_names_array as $class_name) {
        $class_object = call_user_func( $class_name . '::getConstants' );
        foreach($class_object as $key => $value) {
            delete_option($value);
        }
    }
}
?>