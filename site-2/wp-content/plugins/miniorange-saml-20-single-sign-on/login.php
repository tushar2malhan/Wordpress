<?php
/**
 * Plugin Name: miniOrange SSO using SAML 2.0
 * Plugin URI: https://miniorange.com/
 * Description: miniOrange SAML plugin allows sso/login using Azure, Azure B2C, Okta, ADFS, Keycloak, Onelogin, Salesforce, Google Apps (Gsuite), Salesforce, Shibboleth, Centrify, Ping, Auth0 and other Identity Providers. It acts as a SAML Service Provider which can be configured to establish a trust between the plugin and IDP to securely authenticate and login the user to WordPress site.
 * Version: 4.9.29
 * Author: miniOrange
 * Author URI: https://miniorange.com/
 * License: MIT/Expat
 * License URI: https://docs.miniorange.com/mit-license
 * Text Domain: miniorange-saml-20-single-sign-on
 */

include_once dirname( __FILE__ ) . '/mo_login_saml_sso_widget.php';
require( 'mo-saml-class-customer.php' );
require( 'mo_saml_settings_page.php' );
require( 'MetadataReader.php' );
include_once 'Utilities.php';
include_once 'mo_saml_logger.php';
include_once  'WPConfigEditor.php';
include_once 'handlers/BaseHandler.php';

class saml_mo_login {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'miniorange_sso_menu' ) );
		add_action( 'admin_init', array ($this, 'mo_saml_redirect_after_activation') );
		add_action( 'admin_init', array( BaseHandler::class, 'mo_save_settings_handler' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
		register_deactivation_hook( __FILE__, array( $this, 'mo_saml_deactivate') );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this,'my_plugin_action_links') );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
		remove_action( 'admin_notices', array( Utilities::class, 'mo_saml_success_message' ) );
		remove_action( 'admin_notices', array( Utilities::class, 'mo_saml_error_message' ) );
		add_action( 'wp_authenticate', array( $this, 'mo_saml_authenticate' ) );
		add_action( 'admin_footer', array( $this, 'feedback_request' ) );
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this,'mo_saml_plugin_action_links') );
		register_activation_hook(__FILE__,array($this,'plugin_activate'));
        add_action('login_form', array( $this, 'mo_saml_modify_login_form' ) );
		add_action('plugins_loaded', array($this, 'mo_saml_load_translations'));
        add_action( 'wp_ajax_skip_entire_plugin_tour', array($this, 'close_welcome_modal'));
		add_action( 'admin_init', array( 'MoSAMLLogger', 'mo_saml_admin_notices' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );

	}

    function close_welcome_modal(){
        update_option('mo_is_new_user',1);

    }

    function mo_saml_redirect_after_activation() {
	    if (get_option('mo_plugin_do_activation_redirect')) {
		    delete_option('mo_plugin_do_activation_redirect');

		    if(!isset($_GET['activate-multi'])) {
			    wp_redirect(admin_url() . 'admin.php?page=mo_saml_settings');
			    exit;
		    }
	    }
    }

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 4.9.09
	 */
	public function log_errors() {
		MoSAMLLogger::log_critical_errors();
	}
	function my_plugin_action_links( $links ) {
		$url = esc_url( add_query_arg(
			'page',
			'mo_saml_settings',
			get_admin_url() . 'admin.php?page=mo_saml_settings&tab=licensing'
		) );

		$license_link = "<a href='$url'>" . esc_html__( 'Premium Plans' ) . '</a>';

		array_push(
			$links,
			$license_link
		);
		return $links;
	}

	function mo_saml_load_translations(){
		load_plugin_textdomain('miniorange-saml-20-single-sign-on', false, dirname(plugin_basename(__FILE__)). '/resources/lang/');
	}


	function feedback_request() {
		mo_saml_display_saml_feedback_form();
	}

	function mo_login_widget_saml_options() {
		global $wpdb;

		mo_saml_register_saml_sso();
	}

	public function mo_saml_deactivate(){
        delete_option('mo_is_new_user');
		if(!Utilities::mo_saml_is_curl_installed() )
			return;

		$site_home_path = ABSPATH;
		$wp_config_path = $site_home_path . 'wp-config.php';
		$wp_config_editor = new WPConfigEditor($wp_config_path);  //that will be null in case wp-config.php is not writable

		if(is_writeable($wp_config_path)){
			$wp_config_editor->update('MO_SAML_LOGGING', 'false'); //fatal error
		}
		wp_redirect('plugins.php');

	}

	function plugin_settings_style( $page) {
		if ( $page != 'toplevel_page_mo_saml_settings' && !(isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing') && $page != 'miniorange-saml-2-0-sso_page_mo_saml_enable_debug_logs') {
            if($page != 'index.php')
		        return;
		}
		if((isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'licensing') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing') || (isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'save') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_settings') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_enable_debug_logs')){
			wp_enqueue_style( 'mo_saml_bootstrap_css', plugins_url( 'includes/css/bootstrap/bootstrap.min.css', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, 'all' );
		}

		wp_enqueue_style('mo_saml_jquery_ui_style',plugins_url('includes/css/jquery-ui.min.css', __FILE__), array(), mo_saml_options_plugin_constants::Version, 'all');
        wp_enqueue_style( 'mo_saml_admin_gotham_font_style', 'https://fonts.cdnfonts.com/css/gotham', array(), mo_saml_options_plugin_constants::Version, 'all' );
		wp_enqueue_style( 'mo_saml_admin_settings_style', plugins_url( 'includes/css/style_settings.min.css', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, 'all' );
		wp_enqueue_style( 'mo_saml_admin_settings_phone_style', plugins_url( 'includes/css/phone.css', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, 'all' );
		wp_enqueue_style( 'mo_saml_time_settings_style', plugins_url( 'includes/css/datetime-style-settings.min.css', __FILE__ ), array(),mo_saml_options_plugin_constants::Version, 'all' );
		wp_enqueue_style( 'mo_saml_wpb-fa', plugins_url( 'includes/css/style-icon.css', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, 'all' );

	}

	function plugin_settings_script( $page ) {

		if ( $page != 'toplevel_page_mo_saml_settings' && !(isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing') && $page != 'miniorange-saml-2-0-sso_page_mo_saml_enable_debug_logs') {
			return;
		}
		wp_localize_script( 'rml-script', 'readmelater_ajax', array( 'ajax_url' => admin_url('admin-ajax.php')) );


		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-autocomplete');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('mo_saml_select2_script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js');
		wp_enqueue_script('mo_saml_timepicker_script', plugins_url( 'includes/js/jquery.timepicker.min.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
		wp_enqueue_script( 'mo_saml_admin_settings_script', plugins_url( 'includes/js/settings.min.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
		wp_enqueue_script( 'mo_saml_admin_settings_phone_script', plugins_url( 'includes/js/phone.min.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );

		if((isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'licensing') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing')){
			wp_enqueue_script( 'mo_saml_modernizr_script', plugins_url( 'includes/js/modernizr.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
			wp_enqueue_script( 'mo_saml_popover_script', plugins_url( 'includes/js/bootstrap/popper.min.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
			wp_enqueue_script( 'mo_saml_bootstrap_script', plugins_url( 'includes/js/bootstrap/bootstrap.min.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
			wp_enqueue_script('mo_saml_fontawesome_script', plugins_url('includes/js/fontawesome.js', __FILE__ ), array(), mo_saml_options_plugin_constants::Version, false );
		}
	}

    function mo_saml_modify_login_form() {
        if(get_option('mo_saml_add_sso_button_wp') == 'true')
            $this->mo_saml_add_sso_button();
    }

    function mo_saml_add_sso_button() {
        if(!is_user_logged_in()){
            $saml_idp_name = get_option('saml_identity_name');
            $customButtonText = $saml_idp_name ? 'Login with '. $saml_idp_name : 'Login with SSO';
			$html = '
                <script>
                window.onload = function() {
	                var target_btn = document.getElementById("mo_saml_button");
	                var before_element = document.querySelector("#loginform p");
	                before_element.before(target_btn);
                };                  
                    function loginWithSSOButton(id) {
                        if( id === "mo_saml_login_sso_button")
                            document.getElementById("saml_user_login_input").value = "saml_user_login";
                        document.getElementById("loginform").submit(); 
                    }
				</script>
                <input id="saml_user_login_input" type="hidden" name="option" value="">
                <div id="mo_saml_button" style="height:88px;">
                	<div id="mo_saml_login_sso_button" onclick="loginWithSSOButton(this.id)" style="width:100%;display:flex;justify-content:center;align-items:center;font-size:14px;margin-bottom:1.3rem" class="button button-primary">
                    <img style="width:20px;height:15px;padding-right:1px" src="'. esc_url(plugin_dir_url(__FILE__)) . 'images/lock-icon.png">'.esc_html($customButtonText).'
                	</div>
                	<div style="padding:5px;font-size:14px;height:20px;text-align:center"><b>OR</b></div>
            	</div>';
			echo $html;
        }
    }

	function mo_saml_cleanup_logs() {
		$logger = new MoSAMLLogger();
		$retention_period = absint(apply_filters('mo_saml_logs_retention_period',0));
		$timestamp = strtotime( "-{$retention_period} days" );
		if ( is_callable( array( $logger, 'delete_logs_before_timestamp' ) ) ) {
			$logger->delete_logs_before_timestamp($timestamp);
		}
	}
	public function plugin_activate(){
	
		update_option('mo_plugin_do_activation_redirect', true);
	}

	function miniorange_sso_menu() {
		//Add miniOrange SAML SSO
		$slug = 'mo_saml_settings';
		add_menu_page( 'MO SAML Settings ' . __( 'Configure SAML Identity Provider for SSO','miniorange-saml-20-single-sign-on'), 'miniOrange SAML 2.0 SSO', 'administrator', $slug, array(
			$this,
			'mo_login_widget_saml_options'
		), plugin_dir_url( __FILE__ ) . 'images/miniorange.png' );
		add_submenu_page( $slug	,'miniOrange SAML 2.0 SSO'	,__('Plugin Configuration','miniorange-saml-20-single-sign-on'),'manage_options','mo_saml_settings'
			, array( $this, 'mo_login_widget_saml_options'));
		add_submenu_page( $slug	,'miniOrange SAML 2.0 SSO'	,__('<div style="color:orange"><img src="'. plugin_dir_url(__FILE__) . 'images/premium_plans_icon.png" style="height:10px;width:12px">  Premium Plans</div>','miniorange-saml-20-single-sign-on'),'manage_options','mo_saml_licensing'
			, array( $this, 'mo_login_widget_saml_options'));
		add_submenu_page( $slug	,'miniOrange SAML 2.0 SSO'	,__('<div id="mo_saml_addons_submenu">Add-Ons</div>','miniorange-saml-20-single-sign-on'),'manage_options','mo_saml_settings&tab=addons'
			, array( $this, 'mo_login_widget_saml_options'));
		add_submenu_page( $slug	,'miniOrange SAML 2.0 SSO'	,__('<div id="mo_saml_troubleshoot">Troubleshoot</div>','miniorange-saml-20-single-sign-on'),'manage_options','mo_saml_enable_debug_logs'
			, array( 'MoSAMLLogger', 'mo_saml_log_page'));

	}

	function mo_saml_authenticate() {
		$redirect_to = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = sanitize_url( $_REQUEST['redirect_to'] );
		}

		if ( is_user_logged_in() ) {
			$this->mo_saml_login_redirect($redirect_to);
		}
	}

	function mo_saml_login_redirect($redirect_to){
		$is_admin_url = false;

		if(strcmp(admin_url(),$redirect_to) == 0 || strcmp(wp_login_url(),$redirect_to) == 0 ){
			$is_admin_url = true;
		}

		if ( ! empty( $redirect_to ) && !$is_admin_url ) {
			header( 'Location: ' . $redirect_to );
		} else {
			header( 'Location: ' . site_url() );
		}
		exit();
	}

	function mo_saml_plugin_action_links( $links ) {
		$links = array_merge( array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=mo_saml_settings' ) ) . '">' . __( 'Settings','miniorange-saml-20-single-sign-on' ) . '</a>'
		), $links );
		return $links;
	}
}
new saml_mo_login;