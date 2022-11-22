<?php
include_once 'Import-export.php';
require_once 'mo_saml_logger.php';

foreach (glob(plugin_dir_path(__FILE__).'views'.DIRECTORY_SEPARATOR.'*.php') as $filename)
{
    include_once $filename;
}

function mo_saml_register_saml_sso() {
    if ( isset($_GET['tab']) ) {
        $active_tab = sanitize_text_field($_GET['tab']);
        if($active_tab== 'addons')
        {
            echo "<script type='text/javascript'>
            highlightAddonSubmenu();
            </script>";

        }

    } else {
        $active_tab = 'save';
    }
    ?>
    <?php

    mo_saml_display_plugin_dependency_warning();

    ?>
    <div id="mo_saml_settings" >
        <?php
            mo_saml_display_welcome_page();

        mo_saml_display_plugin_header($active_tab);
        ?>

    </div>

    <?php mo_saml_display_plugin_tabs($active_tab);

}

function mo_saml_is_openssl_installed() {

    if ( in_array( 'openssl', get_loaded_extensions() ) ) {
        return 1;
    } else {
        return 0;
    }
}

function mo_saml_is_dom_installed(){

    if ( in_array( 'dom', get_loaded_extensions() ) ) {
        return 1;
    } else {
        return 0;
    }
}

function mo_saml_is_iconv_installed(){

    if ( in_array( 'iconv', get_loaded_extensions() ) ) {
        return 1;
    } else {
        return 0;
    }
}

function mo_saml_get_attribute_mapping_url(){

    return add_query_arg( array('tab' => 'opt'), sanitize_url($_SERVER['REQUEST_URI']) );
}

function mo_saml_get_service_provider_url(){

        return add_query_arg( array('tab' => 'save'), sanitize_url($_SERVER['REQUEST_URI']) );

}
function mo_saml_get_redirection_sso_url(){
    return add_query_arg( array('tab' => 'general'), sanitize_url($_SERVER['REQUEST_URI']) );
}


function mo_saml_get_test_url() {

        $url = site_url() . '/?option=testConfig';


    return $url;
}

function mo_saml_is_customer_registered_saml() {

    $email       = get_option( 'mo_saml_admin_email' );
    $customerKey = get_option( 'mo_saml_admin_customer_key' );

    if ( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
        return 0;
    } else {
        return 1;
    }
}

function mo_saml_display_test_config_error_page($error_code, $error_cause, $error_message, $statusmessage='') {
    echo '<div style="font-family:Calibri;padding:0 3%;">';
    echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;">' . esc_attr_x('ERROR: ' . $error_code,'', 'miniorange-saml-20-single-sign-on') . '</div>
                <div style="color: #a94442;font-size:14pt; margin-bottom:20px;text-align: justify"><p><strong>' . esc_attr_x('Error', '','miniorange-saml-20-single-sign-on') . '</strong>: ' . esc_attr_x($error_cause, '','miniorange-saml-20-single-sign-on') . ' </p>
                
                <p><strong>' . esc_attr_x('Possible Cause: ', '','miniorange-saml-20-single-sign-on') . '</strong>' . esc_attr_x($error_message,'','miniorange-saml-20-single-sign-on') . ' </p>';
    if (!empty($statusmessage))
        echo '<p><strong>Status Message in the SAML Response:</strong> <br/>' . esc_attr_x($statusmessage,'','miniorange-saml-20-single-sign-on') . '</p><br>';
    if($error_code == 'WPSAMLERR010' || $error_code == 'WPSAMLERR004') {
        $option_id = '';
        switch($error_code){
            case 'WPSAMLERR004':
                $option_id = 'mo_fix_certificate';
                break;
            case 'WPSAMLERR010':
                $option_id = 'mo_fix_entity_id';
                break;
        }
        echo '<div>
			    <ol style="text-align: center">
                    <form method="post" action="">';
        wp_nonce_field($option_id);
        echo '<input type="hidden" name="option" value="'.esc_attr_x($option_id, '', 'miniorange-saml-20-single-sign-on') .'" />
                <input type="submit" class="miniorange-button" style="width: 15%" value="' . esc_attr_x('Fix Issue', '','miniorange-saml-20-single-sign-on') . '">
                <br>
                </ol>      
            </form>      
          </div>';
    }
    echo '</div>
        </div>';
}

function mo_saml_download_logs($error_msg,$cause_msg) {

    echo '<div style="font-family:Calibri;padding:0 3%;">';
    echo '<hr class="header"/>';
    echo '          <p style="font-size: larger ;color: #a94442     ">' . wp_kses(__('You can check out the Troubleshooting section provided in the plugin to resolve the issue.<br> If the problem persists, mail us at <a href="mailto:samlsupport@xecurify.com">samlsupport@xecurify.com</a>'), array('br'=>array(), 'a'=>array('href'=>array()))) . '.</p>
                   
                    </div>
                    <div style="margin:3%;display:block;text-align:center;">
                   
				<input class="miniorange-button" style="margin-left:60px" type="button" value="' . esc_attr_x('Close', '', 'miniorange-saml-20-single-sign-on') . '" onclick="self.close()"></form>            
                </div>    ';
    echo '&nbsp;&nbsp;';

    $samlResponse = sanitize_text_field($_POST['SAMLResponse']);
    update_option('MO_SAML_RESPONSE',$samlResponse);
    $error_array  = array("Error"=>$error_msg,"Cause"=>$cause_msg);
    update_option('MO_SAML_TEST',$error_array);
    update_option('MO_SAML_TEST_STATUS',0);
    ?>
    <style>
    .miniorange-button {
    padding:1%;
    background: linear-gradient(0deg,rgb(14 42 71) 0,rgb(26 69 138) 100%)!important;
    cursor: pointer;font-size:15px;
    border-width: 1px;border-style: solid;
    border-radius: 3px;white-space: nowrap;
    box-sizing: border-box;
    box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;
    margin: 22px;
    }
</style>
    <?php

    exit();

}

function mo_saml_add_query_arg($query_arg, $url){
    if(strpos($url, 'mo_saml_licensing') !== false){
        $url = str_replace('mo_saml_licensing', 'mo_saml_settings', $url);
    }
    else if (strpos($url, 'mo_saml_enable_debug_logs') !== false){
	    $url = str_replace('mo_saml_enable_debug_logs', 'mo_saml_settings', $url);
    }
    $url = add_query_arg($query_arg, $url);
    return $url;
}

?>