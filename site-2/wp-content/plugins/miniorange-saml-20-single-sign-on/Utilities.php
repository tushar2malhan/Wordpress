<?php
/**
 * This file is part of miniOrange SAML plugin.
 *
 * miniOrange SAML plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * miniOrange SAML plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange SAML plugin.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once 'xmlseclibs.php';
use \RobRichards\XMLSecLibs\MoXMLSecurityKey;
use \RobRichards\XMLSecLibs\MoXMLSecurityDSig;
use \RobRichards\XMLSecLibs\MoXMLSecEnc;

class Utilities {

    public static function generateID() {
        return '_' . self::stringToHex(self::generateRandomBytes(21));
    }

    public static function stringToHex($bytes) {
        $ret = '';
        for($i = 0; $i < strlen($bytes); $i++) {
            $ret .= sprintf('%02x', ord($bytes[$i]));
        }
        return $ret;
    }

    public static function generateRandomBytes($length, $fallback = TRUE) {

        return openssl_random_pseudo_bytes($length);
    }

    public static function createAuthnRequest($acsUrl, $issuer, $force_authn = 'false') {
        $saml_nameid_format = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
                        '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="' . self::generateID() .
                        '" Version="2.0" IssueInstant="' . self::generateTimestamp() . '"';
        if( $force_authn == 'true') {
            $requestXmlStr .= ' ForceAuthn="true"';
        }
        $requestXmlStr .= ' ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="' . $acsUrl .
                        '" ><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer .
            '</saml:Issuer><samlp:NameIDPolicy AllowCreate="true" Format="' . $saml_nameid_format . '"/></samlp:AuthnRequest>';
        $deflatedStr = gzdeflate($requestXmlStr);
        $base64EncodedStr = base64_encode($deflatedStr);
        $urlEncoded = urlencode($base64EncodedStr);
        update_option('MO_SAML_REQUEST',$base64EncodedStr);

        return $urlEncoded;
    }

    public static function generateTimestamp($instant = NULL) {
        if($instant === NULL) {
            $instant = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $instant);
    }

    public static function xpQuery(DOMNode $node, $query)
    {

        static $xpCache = NULL;

        if ($node instanceof DOMDocument) {
            $doc = $node;
        } else {
            $doc = $node->ownerDocument;
        }

        if ($xpCache === NULL || !$xpCache->document->isSameNode($doc)) {
            $xpCache = new DOMXPath($doc);
            $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
            $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
        }

        $results = $xpCache->query($query, $node);
        $ret = array();
        for ($i = 0; $i < $results->length; $i++) {
            $ret[$i] = $results->item($i);
        }

        return $ret;
    }

    public static function parseNameId(DOMElement $xml)
    {
        $ret = array('Value' => trim($xml->textContent));

        foreach (array('NameQualifier', 'SPNameQualifier', 'Format') as $attr) {
            if ($xml->hasAttribute($attr)) {
                $ret[$attr] = $xml->getAttribute($attr);
            }
        }

        return $ret;
    }

    public static function xsDateTimeToTimestamp($time)
    {
        $matches = array();

        // We use a very strict regex to parse the timestamp.
        $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
        if (preg_match($regex, $time, $matches) == 0) {
            echo sprintf("Invalid SAML2 timestamp passed to xsDateTimeToTimestamp: ". esc_html($time));
            exit;
        }

        // Extract the different components of the time from the  matches in the regex.
        // intval will ignore leading zeroes in the string.
        $year   = intval($matches[1]);
        $month  = intval($matches[2]);
        $day    = intval($matches[3]);
        $hour   = intval($matches[4]);
        $minute = intval($matches[5]);
        $second = intval($matches[6]);

        // We use gmmktime because the timestamp will always be given
        //in UTC.
        $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

        return $ts;
    }

    public static function extractStrings(DOMElement $parent, $namespaceURI, $localName)
    {
        $ret = array();
        for ($node = $parent->firstChild; $node !== NULL; $node = $node->nextSibling) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            }
            $ret[] = trim($node->textContent);
        }

        return $ret;
    }

    public static function validateElement(DOMElement $root)
    {

        /* Create an XML security object. */
        $objXMLSecDSig = new MoXMLSecurityDSig();

        /* Both SAML messages and SAML assertions use the 'ID' attribute. */
        $objXMLSecDSig->idKeys[] = 'ID';


        /* Locate the XMLDSig Signature element to be used. */
        $signatureElement = self::xpQuery($root, './ds:Signature');

        if (count($signatureElement) === 0) {
            /* We don't have a signature element to validate. */
            return FALSE;
        } elseif (count($signatureElement) > 1) {
            echo sprintf("XMLSec: more than one signature element in root.");
            exit;
        }

        $signatureElement = $signatureElement[0];
        $objXMLSecDSig->sigNode = $signatureElement;

        /* Canonicalize the XMLDSig SignedInfo element in the message. */
        $objXMLSecDSig->canonicalizeSignedInfo();

       /* Validate referenced xml nodes. */
        if (!$objXMLSecDSig->validateReference()) {
            echo sprintf("XMLSec: digest validation failed");
            exit;
        }

        /* Check that $root is one of the signed nodes. */
        $rootSigned = FALSE;
        /** @var DOMNode $signedNode */
        foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
            if ($signedNode->isSameNode($root)) {
                $rootSigned = TRUE;
                break;
            } elseif ($root->parentNode instanceof DOMDocument && $signedNode->isSameNode($root->ownerDocument)) {
                /* $root is the root element of a signed document. */
                $rootSigned = TRUE;
                break;
            }
        }

        if (!$rootSigned) {
            echo sprintf("XMLSec: The root element is not signed.");
            exit;
        }

        /* Now we extract all available X509 certificates in the signature element. */
        $certificates = array();
        foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
            $certData = trim($certNode->textContent);
            $certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
            $certificates[] = $certData;
            //echo "CertDate: " . $certData . "<br />";
        }

        $ret = array(
            'Signature' => $objXMLSecDSig,
            'Certificates' => $certificates,
            );

        //echo "Signature validated";


        return $ret;
    }

    public static function validateSignature(array $info, MoXMLSecurityKey $key)
    {

        /** @var MoXMLSecurityDSig $objXMLSecDSig */
        $objXMLSecDSig = $info['Signature'];

        $sigMethod = self::xpQuery($objXMLSecDSig->sigNode, './ds:SignedInfo/ds:SignatureMethod');
        if (empty($sigMethod)) {
            echo sprintf('Missing SignatureMethod element');
            exit();
        }
        $sigMethod = $sigMethod[0];
        if (!$sigMethod->hasAttribute('Algorithm')) {
            echo sprintf('Missing Algorithm-attribute on SignatureMethod element.');
            exit;
        }
        $algo = $sigMethod->getAttribute('Algorithm');

        if ($key->type === MoXMLSecurityKey::RSA_SHA1 && $algo !== $key->type) {
            $key = self::castKey($key, $algo);
        }

        /* Check the signature. */
        if (! $objXMLSecDSig->verify($key)) {
            echo sprintf('Unable to validate Signature');
            exit;
        }
    }

    public static function castKey(MoXMLSecurityKey $key, $algorithm, $type = 'public')
    {

        // do nothing if algorithm is already the type of the key
        if ($key->type === $algorithm) {
            return $key;
        }

        $keyInfo = openssl_pkey_get_details($key->key);
        if ($keyInfo === FALSE) {
            echo sprintf('Unable to get key details from XMLSecurityKey.');
            exit;
        }
        if (!isset($keyInfo['key'])) {
            echo sprintf('Missing key in public key details.');
            exit;
        }

        $newKey = new MoXMLSecurityKey($algorithm, array('type'=>$type));
        $newKey->loadKey($keyInfo['key']);

        return $newKey;
    }

	public static function processResponse( $currentURL, $certFingerprint, $signatureData, SAML2_Response $response, $certNumber, $relayState ) {

		$assertion = current($response->getAssertions());

        $notBefore = $assertion->getNotBefore();
		if ($notBefore !== NULL && $notBefore > time() + 60) {
			MoSAMLLogger::add_log('Received an assertion that is valid in the future. Check clock synchronization on IdP and SP.',MoSAMLLogger::ERROR);
			$error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR007'];
			self::mo_saml_die($error_code);
        }

        $notOnOrAfter = $assertion->getNotOnOrAfter();
		if ($notOnOrAfter !== NULL && $notOnOrAfter <= time() - 60) {
			MoSAMLLogger::add_log('Received an assertion that has expired. Check clock synchronization on IdP and SP.',MoSAMLLogger::ERROR);
            $error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR008'];
            self::mo_saml_die($error_code);
        }

        $sessionNotOnOrAfter = $assertion->getSessionNotOnOrAfter();
		if ($sessionNotOnOrAfter !== NULL && $sessionNotOnOrAfter <= time() - 60) {
			MoSAMLLogger::add_log('Received an assertion with a session that has expired. Check clock synchronization on IdP and SP.',MoSAMLLogger::ERROR);
            $error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR008'];
            self::mo_saml_die($error_code);
        }

        /* Validate Response-element destination. */
        $msgDestination = $response->getDestination();
        if(substr($msgDestination, -1) == '/') {
            $msgDestination = substr($msgDestination, 0, -1);
        }
		if(substr($currentURL, -1) == '/') {
			$currentURL = substr($currentURL, 0, -1);
        }

		if ($msgDestination !== NULL && $msgDestination !== $currentURL) {
			MoSAMLLogger::add_log('Destination in response doesn\'t match the current URL. Destination is "' . esc_url( $msgDestination ) . '", current URL is "' . esc_url( $currentURL ) . '".',$Logger::ERROR);
			echo sprintf('Destination in response doesn\'t match the current URL. Destination is "' .esc_url($msgDestination) . '", current URL is "' . esc_url($currentURL) . '".');
            exit;
        }

        $responseSigned = self::checkSign($certFingerprint, $signatureData, $certNumber,$relayState);

        /* Returning boolean $responseSigned */
        return $responseSigned;
    }


    public static function checkSign($certFingerprint, $signatureData, $certNumber, $relayState) {
        $certificates = $signatureData['Certificates'];

        if (count($certificates) === 0) {
            $storedCerts = maybe_unserialize(get_option('saml_x509_certificate'));
            $pemCert = $storedCerts[$certNumber];
        }else{
            $fpArray = array();
            $fpArray[] = $certFingerprint;
            $pemCert = self::findCertificate($fpArray, $certificates, $relayState);
            if($pemCert==false)
                return false;
        }

        $lastException = NULL;

        $key = new MoXMLSecurityKey(MoXMLSecurityKey::RSA_SHA1, array('type'=>'public'));
        $key->loadKey($pemCert);

        try {
            /*
             * Make sure that we have a valid signature
             */
            self::validateSignature($signatureData, $key);
            return TRUE;
        } catch (Exception $e) {
            $lastException = $e;
        }


        /* We were unable to validate the signature with any of our keys. */
        if ($lastException !== NULL) {
            throw $lastException;
        } else {
            return FALSE;
        }

    }


    public static function validateIssuerAndAudience($samlResponse, $spEntityId, $issuerToValidateAgainst, $relayState) {
        $issuer = current($samlResponse->getAssertions())->getIssuer();
        $assertion = current($samlResponse->getAssertions());
        $audiences = $assertion->getValidAudiences();
        if(strcmp($issuerToValidateAgainst, $issuer) === 0) {
            if(!empty($audiences)) {
                if(in_array($spEntityId, $audiences, TRUE)) {
                    return TRUE;
                } else {
                    MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UTILITIES_INVALID_AUDIENCE_URI',array('spEntityId'=>$spEntityId,'audiences'=>$audiences)), MoSAMLLogger::ERROR);
                    $error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR009'];
                    if($relayState=='testValidate'){
					$error_cause = $error_code['cause'];
					$error_message = $error_code['testConfig_msg'];
                    mo_saml_display_test_config_error_page($error_code['code'],$error_cause, $error_message);
                    mo_saml_download_logs($error_cause,$error_message);
                    exit;
                }
                else
                {
                    Utilities::mo_saml_die($error_code);
                }
                }
            }
        } else {
            MoSAMLLogger::add_log(mo_saml_error_log::showMessage('UTILITIES_INVALID_ISSUER', array('issuerToValidateAgainst' => $issuerToValidateAgainst, 'issuer' => $issuer)), MoSAMLLogger::ERROR);
            $error_code = mo_saml_options_enum_error_codes::$error_codes['WPSAMLERR010'];
            if ($relayState == 'testValidate') {
                $error_cause = $error_code['cause'];
                $error_message = $error_code['testConfig_msg'];
                update_option('mo_saml_required_issuer', $issuer);
                mo_saml_display_test_config_error_page($error_code['code'],$error_cause, $error_message);
                mo_saml_download_logs($error_cause, $error_message);
                exit;
            } else {
                    Utilities::mo_saml_die($error_code);
            }
        }
    }

    private static function findCertificate(array $certFingerprints, array $certificates, $relayState) {

        $candidates = array();

        //foreach ($certificates as $cert) {
            $fp = strtolower(sha1(base64_decode($certificates[0])));
            if (!in_array($fp, $certFingerprints, TRUE)) {
                $candidates[] = $fp;
                return false;
                //continue;
            }

            /* We have found a matching fingerprint. */
            $pem = "-----BEGIN CERTIFICATE-----\n" .
                chunk_split($certificates[0], 64) .
                "-----END CERTIFICATE-----\n";

            return $pem;

    }


    public static function sanitize_certificate( $certificate ) {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        $certificate = str_replace( "-", "", $certificate );
        $certificate = str_replace( "BEGIN CERTIFICATE", "", $certificate );
        $certificate = str_replace( "END CERTIFICATE", "", $certificate );
        $certificate = str_replace( " ", "", $certificate );
        $certificate = chunk_split($certificate, 64, "\r\n");
        $certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
        return $certificate;
    }

    public static function desanitize_certificate( $certificate ) {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        //$certificate = str_replace( "-", "", $certificate );
        $certificate = str_replace( "-----BEGIN CERTIFICATE-----", "", $certificate );
        $certificate = str_replace( "-----END CERTIFICATE-----", "", $certificate );
        $certificate = str_replace( " ", "", $certificate );
        //$certificate = chunk_split($certificate, 64, "\r\n");
        //$certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
        return $certificate;
    }

	public static function mo_saml_wp_remote_post($url, $args = array()){
		$response = wp_remote_post($url, $args);
		if(!is_wp_error($response)){
			return $response['body'];
		} else {
			update_option('mo_saml_message', __('Unable to connect to the Internet. Please try again.','miniorange-saml-20-single-sign-on'));
			(new self)->mo_saml_show_error_message();
			return null;
        }
    }
    
	public static function mo_saml_wp_remote_get($url, $args = array()){
		$response = wp_remote_get($url, $args);
		if(!is_wp_error($response)){
			return $response;
		} else {
			update_option('mo_saml_message', __('Unable to connect to the Internet. Please try again.','miniorange-saml-20-single-sign-on'));
			(new self)->mo_saml_show_error_message();
        }
    }

	public static function mo_saml_show_error_message() {
	    remove_action( 'admin_notices', array( Utilities::class, 'mo_saml_error_message' ) );
	    add_action( 'admin_notices', array( Utilities::class, 'mo_saml_success_message' ) );
	}

	public static function mo_saml_show_success_message() {
	    remove_action( 'admin_notices', array( Utilities::class, 'mo_saml_success_message' ) );
	    add_action( 'admin_notices', array( Utilities::class, 'mo_saml_error_message' ) );
	}

	public static function mo_saml_success_message() {
	    $class   = "error";
	    $message = get_option( 'mo_saml_message' );
        $allowed_html = array('a' => array('href'=>array(),'target'=>array()), 'code' => array());
		echo '<div class="'.esc_html($class).' error_msg" style="display:none;"> <p>'.wp_kses($message, $allowed_html).'</p></div>';
	}

	public static function mo_saml_error_message() {
	    $class   = "updated";
	    $message = get_option( 'mo_saml_message' );
        $allowed_html = array('a' => array('href'=>array(),'target'=>array()), 'code' => array());
	    echo '<div class="'.esc_html($class).' success_msg" style="display:none;"> <p>'.wp_kses($message, $allowed_html).'</p></div>';
	}

    public static function mo_saml_check_empty_or_null( $validate_fields_array ) {
		foreach ( $validate_fields_array as $fields ) {
			if ( !isset( $fields ) || empty( $fields ) )
	        return true;
	    }
	    return false;
	}

    public static function mo_saml_die( $error_code ) {
        wp_die('We could not sign you in. Please contact your administrator with the following error code.<br><br>Error code: <b>'.esc_html($error_code["code"]).'</b>','Error: '.esc_html($error_code["code"]));
    }

    public static function mo_safe_file_get_contents($file){
        set_error_handler('Utilities::mo_handle_file_content_error');
        if(is_uploaded_file($file))
            $file=file_get_contents($file);
        else
            $file='';
        restore_error_handler();
        return $file;
    }

	public static function mo_saml_is_curl_installed() {
		if ( in_array( 'curl', get_loaded_extensions() ) )
			return 1;
		return 0;
	}

	public static function mo_saml_get_sp_base_url() {
		$sp_base_url = get_option(mo_saml_options_enum_identity_providerMoSAML::SP_Base_Url);

		if (empty($sp_base_url))
			$sp_base_url = site_url();

		if (substr($sp_base_url,-1) == '/')
			$sp_base_url = substr( $sp_base_url, 0, - 1 );

		return $sp_base_url;
	}

	public static function mo_saml_get_sp_entity_id($sp_base_url) {
		$sp_entity_id = get_option(mo_saml_options_enum_identity_providerMoSAML::SP_Entity_ID);

		if (empty($sp_entity_id))
			$sp_entity_id = $sp_base_url . '/wp-content/plugins/miniorange-saml-20-single-sign-on/';

		return $sp_entity_id;
	}

	public static function mo_saml_is_sp_configured() {
		$saml_login_url = get_option( 'saml_login_url' );

		if ( empty( $saml_login_url ) ) {
			return 0;
        } else {
			return 1;
        }
    }

    public static function mo_handle_file_content_error($errno, $errstr, $errfile, $errline) {
        if ($errno==E_WARNING ) {
            update_option( 'mo_saml_message', 'Error: An error occurred while reading file content' );
            Utilities::mo_saml_show_error_message();
           return true;
        } else {
            return false;
        }
    }

    public static function mo_saml_get_plugin_dir_url() {
        return plugin_dir_url(__FILE__);
    }

    public static function mo_saml_is_plugin_page($query) {   
        $query_str = parse_url(sanitize_text_field($_SERVER['REQUEST_URI']), PHP_URL_QUERY);
        $query_str = is_null($query_str) ? '' : $query_str;
	    parse_str($query_str, $query_params);
       if((isset($_POST['option']) && ($_POST['option'] == 'mo_skip_feedback' || $_POST['option'] == 'mo_feedback' )) || array_key_exists('page',$query_params) && strpos($query_params['page'], 'mo_saml') !== false){
			return true;
       }
       return false;
    }

}
