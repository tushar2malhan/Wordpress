<?php


class ServiceProviderMetadataHandler {

	public static function download_plugin_metadata ($download = false) {

		$sp_base_url = Utilities::mo_saml_get_sp_base_url();
		$entity_id = Utilities::mo_saml_get_sp_entity_id($sp_base_url);
		$acs_url     = $sp_base_url . '/';

		if(ob_get_contents())
			ob_clean();

		header( 'Content-Type: text/xml' );
		if($download)
			header('Content-Disposition: attachment; filename="Metadata.xml"');

		echo '<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" validUntil="2022-10-28T23:59:59Z" cacheDuration="PT1446808792S" entityID="' . esc_attr($entity_id) . '">
    <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
	    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . esc_url($acs_url) . '" index="1"/>
    </md:SPSSODescriptor>
	<md:Organization>
		<md:OrganizationName xml:lang="en-US">miniOrange</md:OrganizationName>
		<md:OrganizationDisplayName xml:lang="en-US">miniOrange</md:OrganizationDisplayName>
		<md:OrganizationURL xml:lang="en-US">http://miniorange.com</md:OrganizationURL>
	</md:Organization>
	<md:ContactPerson contactType="technical">
		<md:GivenName>miniOrange</md:GivenName>
		<md:EmailAddress>info@xecurify.com</md:EmailAddress>
	</md:ContactPerson>
	<md:ContactPerson contactType="support">
		<md:GivenName>miniOrange</md:GivenName> 
		<md:EmailAddress>info@xecurify.com</md:EmailAddress>
	</md:ContactPerson>
</md:EntityDescriptor>';
		exit;
	}

	public static function update_sp_endpoints ($post_array, $db_handler) {

		if(isset($post_array[mo_saml_options_enum_identity_providerMoSAML::SP_Entity_ID])) {
			$save_array[mo_saml_options_enum_identity_providerMoSAML::SP_Entity_ID] = sanitize_text_field($post_array[mo_saml_options_enum_identity_providerMoSAML::SP_Entity_ID]);
			$db_handler->save_options($save_array);

			$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::SETTINGS_UPDATED, 'SP_ENTITY_ID', $save_array);
			$post_save->post_save_action();
		}
	}
}