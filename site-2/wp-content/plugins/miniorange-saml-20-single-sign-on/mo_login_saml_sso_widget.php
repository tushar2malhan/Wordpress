<?php
include_once dirname(__FILE__) . '/Utilities.php';
include_once dirname(__FILE__) . '/Response.php';
require_once dirname(__FILE__) . '/includes/lib/mo-saml-options-enum.php';
require_once dirname(__FILE__) . '/includes/lib/mo-saml-error-codes-enum.php';
include_once 'xmlseclibs.php';

use \RobRichards\XMLSecLibs\MoXMLSecurityKey;

include_once 'Import-export.php';
class mo_login_wid extends WP_Widget
{
	public function __construct()
	{
		$identityName = get_option('saml_identity_name');
		parent::__construct(
			'Saml_Login_Widget',
			'Login with ' . esc_html($identityName),
			array(
				'description' => esc_html__('This is a miniOrange SAML login widget.', 'miniorange-saml-20-single-sign-on'),
				'customize_selective_refresh' => true,
			)
		);
	}


	public function widget($args, $instance)
	{
		extract($args);
		$wid_title = '';
		if (array_key_exists('wid_title', $instance))
			$wid_title = $instance['wid_title'];
		$wid_title = apply_filters('widget_title', $wid_title);

		echo $args['before_widget'];
		if (!empty($wid_title))
			echo $args['before_title'] . esc_html($wid_title) . $args['after_title'];
		$this->loginForm();
		echo $args['after_widget'];
	}

	public function update($new_instance, $old_instance)
	{
		$instance = array();
		$instance['wid_title'] = isset($new_instance['wid_title']) ? sanitize_text_field($new_instance['wid_title']) : '';
		return $instance;
	}


	public function form($instance)
	{
		$wid_title = '';
		if (array_key_exists('wid_title', $instance))
			$wid_title = $instance['wid_title'];
?>
        <p><label for="<?php esc_attr($this->get_field_id('wid_title')); ?>"><?php esc_html_e('Title:', 'miniorange-saml-20-single-sign-on'); ?> </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('wid_title')); ?>" name="<?php esc_attr($this->get_field_name('wid_title')); ?>" type="text" value="<?php esc_attr($wid_title); ?>" />
		</p>
		<?php
	}

	public function loginForm()
	{
		if (!is_user_logged_in()) {
		?>
			<script>
				function submitSamlForm() {
					document.getElementById("miniorange-saml-sp-sso-login-form").submit();
				}
			</script>
			<form name="miniorange-saml-sp-sso-login-form" id="miniorange-saml-sp-sso-login-form" method="post" action="">
				<input type="hidden" name="option" value="saml_user_login" />

				<font size="+1" style="vertical-align:top;"> </font><?php
                $identity_provider = get_option('saml_identity_name');
                $saml_x509_certificate = get_option('saml_x509_certificate');
                if (!empty($identity_provider) && !empty($saml_x509_certificate)) {
					echo '<a href="#" onClick="submitSamlForm()">Login with ' . esc_html($identity_provider) . '</a></form>';
                } else
                    esc_html_e('Please configure the miniOrange SAML Plugin first.', 'miniorange-saml-20-single-sign-on');

                if (!Utilities::mo_saml_check_empty_or_null(array(get_option('mo_saml_redirect_error_code')))) {

                    echo '<div></div><div title="Login Error"><font color="red">' . esc_html__('We could not sign you in. Please contact your Administrator.','miniorange-saml-20-single-sign-on') . '</font></div>';

                    delete_option('mo_saml_redirect_error_code');
                    delete_option('mo_saml_redirect_error_reason');
                }

                ?>



				</ul>
			</form>
		<?php
		} else {
			$current_user = wp_get_current_user();
			$link_with_username = sprintf(__('Hello, %s', 'miniorange-saml-20-single-sign-on'), $current_user->display_name);
		?>
			<?php esc_html_e($link_with_username, 'miniorange-saml-20-single-sign-on'); ?> | <a href="<?php echo esc_url(wp_logout_url(saml_get_current_page_url())); ?>" title="<?php esc_attr_e('Logout', 'miniorange-saml-20-single-sign-on'); ?>"><?php esc_html_e('Logout', 'miniorange-saml-20-single-sign-on'); ?></a></li>
<?php
		}
	}

}

function mo_saml_login_validate()
{
	if (isset($_REQUEST['option']) && $_REQUEST['option'] == 'mosaml_metadata') {
		ServiceProviderMetadataHandler::download_plugin_metadata();
	}
	if (isset($_REQUEST['option']) && $_REQUEST['option'] == 'export_configuration') {
		if (current_user_can('manage_options'))
			mo_saml_miniorange_import_export(true);
		exit;
	}
	if (isset($_REQUEST['option']) && $_REQUEST['option'] == 'mo_fix_certificate' && is_user_logged_in() && current_user_can('manage_options')) {

		$saml_required_certificate = get_option('mo_saml_required_certificate');
		$saml_certificate =  maybe_unserialize(get_option(mo_saml_options_enum_service_providerMoSAML::X509_certificate));
		$saml_certificate[0] = Utilities::sanitize_certificate($saml_required_certificate);
		update_option(mo_saml_options_enum_service_providerMoSAML::X509_certificate, $saml_certificate);
		wp_redirect('?option=testConfig');
		exit;
	}
	if (isset($_REQUEST['option']) && $_REQUEST['option'] == 'mo_fix_entity_id' && is_user_logged_in() && current_user_can('manage_options')) {

		$saml_required_issuer = get_option('mo_saml_required_issuer');
		update_option(mo_saml_options_enum_service_providerMoSAML::Issuer, $saml_required_issuer);
		wp_redirect('?option=testConfig');
		exit;
	}
	if ((isset($_REQUEST['option']) && $_REQUEST['option'] == 'saml_user_login') || (isset($_REQUEST['option']) && $_REQUEST['option'] == 'testConfig')) {

		if ($_REQUEST['option'] == 'testConfig') {
			if (!is_user_logged_in() || is_user_logged_in() && !current_user_can('manage_options')) {
				return;
			}
		} else {
			if (is_user_logged_in())
				return;
		}

		if (Utilities::mo_saml_is_sp_configured()) {
			if ($_REQUEST['option'] == 'testConfig')
				$sendRelayState = 'testValidate';
			else if (isset($_REQUEST['redirect_to']))
				$sendRelayState = sanitize_text_field($_REQUEST['redirect_to']);
			else
				$sendRelayState = saml_get_current_page_url();

			$sendRelayState = urlencode($sendRelayState);
			$sp_base_url = get_option('mo_saml_sp_base_url');
			if (empty($sp_base_url)) {
				$sp_base_url = site_url();
			}

			$ssoUrl = htmlspecialchars_decode(get_option("saml_login_url"));
			$acsUrl = site_url() . "/";
			$issuer = site_url() . '/wp-content/plugins/miniorange-saml-20-single-sign-on/';
			$sp_entity_id = get_option('mo_saml_sp_entity_id');
			if (empty($sp_entity_id)) {
				$sp_entity_id = $sp_base_url . '/wp-content/plugins/miniorange-saml-20-single-sign-on/';
			}

			$log_message =  [
				'ssoUrl' => $ssoUrl,
				'acsUrl' =>  $acsUrl,
				'sp_entity_id' => $sp_entity_id,
				'sendRelayState' => $sendRelayState
			];
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_AUTHN_REQUEST', $log_message), MoSAMLLogger::DEBUG);
			$samlRequest = Utilities::createAuthnRequest($acsUrl, $sp_entity_id);

			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_SAML_REQUEST', array('samlRequest' => $samlRequest)), MoSAMLLogger::DEBUG);

			$redirect = $ssoUrl;

			if (strpos($ssoUrl, '?') !== false) {
				$redirect .= '&';
			} else {
				$redirect .= '?';
			}
			$redirect .= 'SAMLRequest=' . $samlRequest . '&RelayState=' . $sendRelayState;

			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_RELAYSTATE_SENT', array('redirect' => $redirect)), MoSAMLLogger::DEBUG);

			header('Location: ' . $redirect);
			exit();
		}
	}
	if (array_key_exists('SAMLResponse', $_POST) && !empty($_POST['SAMLResponse'])) {

		$samlResponse = sanitize_text_field($_POST['SAMLResponse']);
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_SAML_RESPONSE', array('samlResponse' => $samlResponse)), MoSAMLLogger::DEBUG);
		if (array_key_exists('RelayState', $_POST) && !empty($_POST['RelayState']) && $_POST['RelayState'] != '/') {
			$relayState = sanitize_text_field($_POST['RelayState']);
		} else {
			$relayState = '';
		}
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_RELAYSTATE_RECEIVED', array('relayState' => $relayState)), MoSAMLLogger::DEBUG);
		update_option('MO_SAML_RESPONSE', $samlResponse);

		$samlResponse = base64_decode($samlResponse);

		$document = new DOMDocument();
		$document->loadXML($samlResponse);
		$samlResponseXml = $document->firstChild;

		$doc = $document->documentElement;
		$xpath = new DOMXpath($document);
		$xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
		$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

		$status = $xpath->query('/samlp:Response/samlp:Status/samlp:StatusCode', $doc);
		$statusString = $status->item(0)->getAttribute('Value');
		$statusMessage = $xpath->query('/samlp:Response/samlp:Status/samlp:StatusMessage', $doc)->item(0);
		if (!empty($statusMessage))
			$statusMessage = $statusMessage->nodeValue;

		$statusArray = explode(':', $statusString);
		if (array_key_exists(7, $statusArray)) {
			$status = $statusArray[7];
		}
		if ($status != "Success") {
			mo_saml_show_status_error($status, $relayState, $statusMessage);
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_INVAILD_SAML_STATUS'), MoSAMLLogger::ERROR);
		}


		$certFromPlugin = maybe_unserialize(get_option('saml_x509_certificate'));

		$acsUrl = site_url() . '/';
		$samlResponse = new SAML2_Response($samlResponseXml);
		$responseSignatureData = $samlResponse->getSignatureData();
		$assertionSignatureData = current($samlResponse->getAssertions())->getSignatureData();

		if (empty($assertionSignatureData) && empty($responseSignatureData)) {
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_RESPONSE_ASSERATION_NOT_SIGNED'), MoSAMLLogger::ERROR);
			$error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR003'];
			if ($relayState == 'testValidate') {

				$error_cause = $error_code['cause'];
				$error_message = $error_code['testConfig_msg'];
                mo_saml_display_test_config_error_page($error_code['code'], $error_cause, $error_message);
                mo_saml_download_logs($error_cause, $error_message);
			exit;
			} else {
				Utilities::mo_saml_die($error_code);
			}
		}
		if (is_array($certFromPlugin)) {
			foreach ($certFromPlugin as $key => $value) {
				$certfpFromPlugin = MoXMLSecurityKey::getRawThumbprint($value);

				$certfpFromPlugin = mo_saml_convert_to_windows_iconv($certfpFromPlugin);
				$certfpFromPlugin = preg_replace('/\s+/', '', $certfpFromPlugin);
				if (!empty($responseSignatureData)) {
					$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $responseSignatureData, $samlResponse, $key, $relayState);
				}
				if (!empty($assertionSignatureData)) {
					$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $assertionSignatureData, $samlResponse, $key, $relayState);
				}
				if ($validSignature)
					break;
			}
		} else {
			$certfpFromPlugin = MoXMLSecurityKey::getRawThumbprint($certFromPlugin);
			$certfpFromPlugin = mo_saml_convert_to_windows_iconv($certfpFromPlugin);
			$certfpFromPlugin = preg_replace('/\s+/', '', $certfpFromPlugin);
			if (!empty($responseSignatureData)) {
				$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $responseSignatureData, $samlResponse, 0, $relayState);
			}

			if (!empty($assertionSignatureData)) {
				$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $assertionSignatureData, $samlResponse, 0, $relayState);
			}
		}
		if ($responseSignatureData)
			$saml_required_certificate = $responseSignatureData['Certificates'][0];
		elseif ($assertionSignatureData)
			$saml_required_certificate = $assertionSignatureData['Certificates'][0];
		update_option('mo_saml_required_certificate', $saml_required_certificate);
		if (!$validSignature) {
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_CERT_NOT_MATCHED'), MoSAMLLogger::ERROR);
			$error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR004'];
			if ($relayState == 'testValidate') {
				$error_cause = $error_code['cause'];
				$error_message = $error_code['testConfig_msg'];
                mo_saml_display_test_config_error_page($error_code['code'],$error_cause, $error_message);
				mo_saml_download_logs($error_cause, $error_message);
				exit;
			} else {
                Utilities::mo_saml_die($error_code);
			}
		}

		$sp_base_url = get_option('mo_saml_sp_base_url');
		if (empty($sp_base_url)) {
			$sp_base_url = site_url();
		}
		// verify the issuer and audience from saml response
		$issuer = get_option('saml_issuer');
		$spEntityId = get_option('mo_saml_sp_entity_id');
		if (empty($spEntityId)) {
			$spEntityId = $sp_base_url . '/wp-content/plugins/miniorange-saml-20-single-sign-on/';
		}
		Utilities::validateIssuerAndAudience($samlResponse, $spEntityId, $issuer, $relayState);

		$ssoemail = current(current($samlResponse->getAssertions())->getNameId());
		$attrs = current($samlResponse->getAssertions())->getAttributes();
		$attrs['NameID'] = array("0" => sanitize_text_field($ssoemail));
		$sessionIndex = current($samlResponse->getAssertions())->getSessionIndex();
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('ATTRIBUTES_RECEIVED_IN_TEST_CONFIGURATION', ['attrs' => $attrs]), MoSAMLLogger::INFO);
		mo_saml_checkMapping($attrs, $relayState, $sessionIndex);
	}
}

function mo_saml_checkMapping($attrs, $relayState, $sessionIndex)
{
	try {
		//Get enrypted user_email
		$emailAttribute = get_option('saml_am_email');
		$mo_saml_identity_provider_identifier_name = get_option('mo_saml_identity_provider_identifier_name') ? get_option('mo_saml_identity_provider_identifier_name') : "";
		if (!empty($mo_saml_identity_provider_identifier_name) and $mo_saml_identity_provider_identifier_name == 'Azure B2C') {
			$emailAttribute = 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress';
		}
		$usernameAttribute = get_option('saml_am_username');
		$firstName = get_option('saml_am_first_name');
		$lastName = get_option('saml_am_last_name');
		$groupName = get_option('saml_am_group_name');
		$defaultRole = get_option('saml_am_default_user_role');
		$checkIfMatchBy = get_option('saml_am_account_matcher');
		$user_email = '';
		$userName = '';

		//Attribute mapping. Check if Match/Create user is by username/email:
		if (!empty($attrs)) {
			if (!empty($firstName) && array_key_exists($firstName, $attrs))
				$firstName = $attrs[$firstName][0];
			else
				$firstName = '';

			if (!empty($lastName) && array_key_exists($lastName, $attrs))
				$lastName = $attrs[$lastName][0];
			else
				$lastName = '';

			if (!empty($usernameAttribute) && array_key_exists($usernameAttribute, $attrs))
				$userName = $attrs[$usernameAttribute][0];
			else
				$userName = $attrs['NameID'][0];

			if (!empty($emailAttribute) && array_key_exists($emailAttribute, $attrs))
				$user_email = $attrs[$emailAttribute][0];
			else
				$user_email = $attrs['NameID'][0];

			if (!empty($groupName) && array_key_exists($groupName, $attrs))
				$groupName = $attrs[$groupName];
			else
				$groupName = array();

			if (empty($checkIfMatchBy)) {
				$checkIfMatchBy = "email";
			}
		}


		if ($relayState == 'testValidate') {
			update_option('MO_SAML_TEST', "Test successful");
			update_option('MO_SAML_TEST_STATUS', 1);
			mo_saml_show_test_result($firstName, $lastName, $user_email, $groupName, $attrs);
		} else {
			mo_saml_login_user($user_email, $firstName, $lastName, $userName, $groupName, $defaultRole, $relayState, $checkIfMatchBy, $sessionIndex, $attrs['NameID'][0]);
		}
	} catch (Exception $e) {
		echo sprintf("An error occurred while processing the SAML Response.");
		exit;
	}
}



function mo_saml_show_test_result($firstName, $lastName, $user_email, $groupName, $attrs)
{
	if (ob_get_contents())
		ob_end_clean();
	echo '<div style="font-family:Calibri;padding:0 3%;">';
	$name_id = $attrs['NameID'][0];
	if (!empty($user_email)) {
		update_option('mo_saml_test_config_attrs', $attrs);
		echo '<div style="color: #3c763d;
				background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt; border-radius:10px;margin-top:17px;">TEST SUCCESSFUL</div>
				<div style="display:block;text-align:center;margin-bottom:4%;"><svg class="animate" width="100" height="100">
				<filter id="dropshadow" height="">
				  <feGaussianBlur in="SourceAlpha" stdDeviation="3" result="blur"></feGaussianBlur>
				  <feFlood flood-color="rgba(76, 175, 80, 1)" flood-opacity="0.5" result="color"></feFlood>
				  <feComposite in="color" in2="blur" operator="in" result="blur"></feComposite>
				  <feMerge> 
					<feMergeNode></feMergeNode>
					<feMergeNode in="SourceGraphic"></feMergeNode>
				  </feMerge>
				</filter>
				
				<circle cx="50" cy="50" r="46.5" fill="none" stroke="rgba(76, 175, 80, 0.5)" stroke-width="5"></circle>
				
				<path d="M67,93 A46.5,46.5 0,1,0 7,32 L43,67 L88,19" fill="none" stroke="rgba(76, 175, 80, 1)" stroke-width="5" stroke-linecap="round" stroke-dasharray="80 1000" stroke-dashoffset="-220" style="filter:url(#dropshadow)"></path>
			  </svg><style>
			  svg.animate path {
			  animation: dash 1.5s linear both;
			  animation-delay: 1s;
			}
			  @keyframes dash {
			  0% { stroke-dashoffset: 210; }
			  75% { stroke-dashoffset: -220; }
			  100% { stroke-dashoffset: -205; }
			}
			</style></div>';
	} else {
		echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;">TEST FAILED</div>
				<div style="color: #a94442;font-size:14pt; margin-bottom:20px;">WARNING: Some Attributes Did Not Match.</div>
				<div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;"src="' . esc_url(plugin_dir_url(__FILE__)) . 'images/wrong.png"></div>';
	}

	if (strlen($name_id) > 60) {
		echo '<p><font color="#FF0000" style="font-size:14pt;font-weight:bold">Warning: The NameID value is longer than 60 characters. User will not be created during SSO.</font></p>';
	}
	$matchAccountBy = get_option('saml_am_account_matcher') ? get_option('saml_am_account_matcher') : 'email';
	if ($matchAccountBy == 'email' && !filter_var($name_id, FILTER_VALIDATE_EMAIL)) {
		echo '<p><font color="#FF0000" style="font-size:14pt;font-weight:bold">Warning: The NameID value is not a valid Email ID</font></p>';
	}
	echo '<span style="font-size:14pt;"><b>Hello</b>, ' . esc_html($user_email) . '</span>';


	echo '<br/><p style="font-weight:bold;font-size:14pt;margin-left:1%;">Attributes Received:</p>
				<table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:14pt;word-break:break-all;">
				<tr style="text-align:center;background:#d3e1ff;border:2.5px solid #ffffff";word-break:break-all;><td style="font-weight:bold;padding:2%;border-top-left-radius: 10px;border:2.5px solid #ffffff">ATTRIBUTE NAME</td><td style="font-weight:bold;padding:2%;border:2.5px solid #ffffff; word-wrap:break-word;border-top-right-radius:10px">ATTRIBUTE VALUE</td></tr>';

	if (!empty($attrs)) {
		foreach ($attrs as $key => $value) {
            if(is_array($value))
	            $attr_values = implode("<hr>", $value);
            else
                $attr_values = esc_html($value);
            $allowed_html = array('hr' => array());
			echo "<tr><td style='border:2.5px solid #ffffff;padding:2%;background:#e9f0ff;'>" . esc_html( $key ) . "</td><td style='padding:2%;border:2.5px solid #ffffff;background:#e9f0ff;word-wrap:break-word;'>" . wp_kses($attr_values, $allowed_html) . "</td></tr>";
		}
	} else
		echo "No Attributes Received.";
	echo '</table></div>';
	echo '<div style="margin:3%;display:block;text-align:center;">
		<input style="padding:1%;width:250px;background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"
            type="button" value="Configure Attribute/Role Mapping" onClick="close_and_redirect_to_attribute_mapping();"> &nbsp;
		<input style="padding:1%;width:250px;background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;
		"type="button" value="Configure SSO Settings" onClick="close_and_redirect_to_redir_sso();"></div>
		
		<script>
             function close_and_redirect_to_attribute_mapping(){
                 window.opener.redirect_to_attribute_mapping();
                 self.close();
             }   
             function close_and_redirect() {
               window.opener.redirect_to_service_provider();
                 self.close();
             }
             function close_and_redirect_to_redir_sso() {
               window.opener.redirect_to_redi_sso_link();
                 self.close();
             }
             
            
		</script>';
	exit;
}


/**
 * @Author:Shubham Gupta
 *
 */
function mo_saml_convert_to_windows_iconv($certfpFromPlugin)
{
	$encoding_enabled = get_option('mo_saml_encoding_enabled');

	if ($encoding_enabled === '' || !mo_saml_is_iconv_installed())
		return $certfpFromPlugin;
	return iconv("UTF-8", "CP1252//IGNORE", $certfpFromPlugin);
}

function mo_saml_login_user($user_email, $firstName, $lastName, $userName, $groupName, $defaultRole, $relayState, $checkIfMatchBy, $sessionIndex = '', $nameId = '')
{
	$user_id = null;
	if (($checkIfMatchBy == 'username' && username_exists($userName)) || username_exists($userName)) {
		$user 	= get_user_by('login', $userName);
		$user_id = $user->ID;

		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_USER_EXISTS', array('userName' => $userName)), MoSAMLLogger::DEBUG);
	} elseif (email_exists($user_email)) {

		$user 	= get_user_by('email', $user_email);
		$user_id = $user->ID;
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_EMAIL_EXISTS', array('user_email' => $user_email)), MoSAMLLogger::DEBUG);
	} elseif (!username_exists($userName) && !email_exists($user_email)) {
		$random_password = wp_generate_password(10, false);
		if (!empty($userName)) {
			$user_id = wp_create_user($userName, $random_password, $user_email);
		} else {
			$user_id = wp_create_user($user_email, $random_password, $user_email);
		}
		if (is_wp_error($user_id)) {
			MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_USER_CREATION_FAILED'), MoSAMLLogger::ERROR);
			$error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR005'];
			wp_die('We couldn\'t sign you in. Please contact your administrator with the following error code.<br><br>Error code: <b>'.esc_attr($error_code['code']).'</b>.', 'Error: User not created');
			exit();
		}
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_NEW_USER', array('user_email' => $user_email, 'user_id' => $user_id)), MoSAMLLogger::DEBUG);

        if (!empty($defaultRole) and !is_administrator_user(get_user_by('id',$user_id))) {
            $user_id = wp_update_user(array('ID' => $user_id, 'role' => $defaultRole));
	        MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_DEFAULT_ROLE', array('defaultRole' => $defaultRole)), MoSAMLLogger::DEBUG);
        }
    }
	mo_saml_add_firstlast_name($user_id, $firstName, $lastName, $relayState);
}

function is_administrator_user( $user ) {
    $userRole = ($user->roles);
    if(!is_null($userRole) && in_array('administrator', $userRole, TRUE))
        return true;
    else
        return false;
}

function mo_saml_add_firstlast_name($user_id, $first_name, $last_name, $relay_state)
{
	if (!empty($first_name)) {
		$user_id = wp_update_user(array('ID' => $user_id, 'first_name' => $first_name));
	}
	if (!empty($last_name)) {
		$user_id = wp_update_user(array('ID' => $user_id, 'last_name' => $last_name));
	}

	MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_COOKIE_CREATED', array('user_id' => $user_id)), MoSAMLLogger::DEBUG);
	wp_set_auth_cookie($user_id, true);

	if (!empty($relay_state))
		$redirect_url = $relay_state;
	else
		$redirect_url = site_url();
	MoSAMLLogger::add_log(mo_saml_error_log::showMessage('LOGIN_WIDGET_REDIRECT_URL_AFTER_LOGIN', array('redirect_url' => $redirect_url)), MoSAMLLogger::DEBUG);

	wp_redirect($redirect_url);
	exit;
}


function mo_saml_show_status_error($statusCode, $relayState, $statusmessage)
{
	$statusCode = sanitize_text_field($statusCode);
	$statusmessage = sanitize_text_field($statusmessage);
	$error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR006'];
	if ($relayState == 'testValidate') {
        $error_cause = $error_code['cause'];
        $error_message = sprintf($error_code['testConfig_msg'], $statusCode);
        mo_saml_display_test_config_error_page($error_code['code'],$error_cause, $error_message, $statusmessage);
		mo_saml_download_logs($error_cause, $error_message);
		exit;
	} else {
		Utilities::mo_saml_die($error_code);
	}
}
function addLink($title, $link)
{
	$html = '<a href="' . esc_url($link) . '">' . esc_html($title) . '</a>';
	return $html;
}

function saml_get_current_page_url()
{
    $http_host = sanitize_url($_SERVER['HTTP_HOST']);
    $is_https = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') == 0);

    if(filter_var($http_host, FILTER_VALIDATE_URL)) {
        $http_host = parse_url($http_host, PHP_URL_HOST);
    }
    $request_uri = sanitize_url($_SERVER['REQUEST_URI']);
    if (substr($request_uri, 0, 1) == '/') {
        $request_uri = substr($request_uri, 1);
    }
    if (strpos($request_uri, '?option=saml_user_login') !== false) {
        return strtok(sanitize_url($_SERVER["REQUEST_URI"]), '?');
    }
    $relay_state = 'http' . ($is_https ? 's' : '') . '://' . $http_host . '/' . $request_uri;
    return $relay_state;
}
add_action('widgets_init', function () {
	register_widget("mo_login_wid");
});
add_action('init', 'mo_saml_login_validate');
?>