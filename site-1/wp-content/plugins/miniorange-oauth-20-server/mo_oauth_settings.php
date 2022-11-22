<?php
/**
* Plugin Name: miniOrange OAuth 2.0 Server/Provider
* Plugin URI: http://miniorange.com
* Description: Setup your site as Identity Server to allow Login with WordPress or WordPress Login to other client application /site using OAuth / OpenID Connect protocols.
* Version: 5.0.5
* Author: miniOrange
* Author URI: https://www.miniorange.com
* License: MIT/Expat
* License URI: https://docs.miniorange.com/mit-license
*/
define( 'MOSERVER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOSERVER_URL', plugin_dir_url( __FILE__ ) );
require('feedback_form.php');
require('class-customer.php');
require('mo_oauth_settings_page.php');
require('mo_oauth_db_handler.php');
require( 'endpoints'.DIRECTORY_SEPARATOR.'registry.php' );
require('demo'.DIRECTORY_SEPARATOR.'class-mo-oauth-server-demo.php');

class mo_oauth_server {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'miniorange_menu' ) );
		add_filter('init','mo_oauth_server_authorize');
		add_action( 'admin_init',  array( $this, 'miniorange_oauth_save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
		register_deactivation_hook(__FILE__, array( $this, 'mo_oauth_deactivate'));
		register_activation_hook(__FILE__, array( $this, 'mo_oauth_activate'));
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
		remove_action( 'admin_notices', array( $this, 'mo_oauth_success_message') );
		remove_action( 'admin_notices', array( $this, 'mo_oauth_error_message') );
		add_action( 'admin_footer', array( $this, 'mo_oauth_server_feedback_request' ) );
		// Security warning notice
		add_action( 'admin_notices','mo_oauth_server_security_warning_message' );	
	}

	function mo_oauth_success_message() {
		$class = "error";
		$message = get_option('message');
		echo "<div class='" . esc_attr($class) . "'> <p>" . esc_attr($message) . "</p></div>";
	}

	function mo_oauth_server_feedback_request() {
		mo_oauth_server_display_feedback_form();
	}

	function mo_oauth_error_message() {
		$class = "updated";
		$message = get_option('message');
		echo "<div class='" . esc_attr($class) . "'><p>" . esc_attr($message) . "</p></div>";
	}

	public function mo_oauth_activate() {
		$moOauthServerDb = new MoOauthServerDb();
		$moOauthServerDb->mo_plugin_activate();
	}

	public function mo_oauth_deactivate() {
		//delete all stored key-value pairs
		delete_option('host_name');
		delete_option('mo_oauth_server_new_registration');
		delete_option('mo_oauth_server_admin_phone');
		delete_option('mo_oauth_server_verify_customer');
		delete_option('mo_oauth_server_admin_customer_key');
		delete_option('mo_oauth_server_admin_api_key');
		delete_option('mo_oauth_server_new_customer');
		delete_option('mo_oauth_server_customer_token');
		delete_option('message');
		delete_option('mo_oauth_server_registration_status');
		delete_option('mo_oauth_show_mo_server_message');
		delete_option('mo_oauth_server_hide_security_warning_admin');
		delete_option('mo_oauth_server_security_warning_remind_date');
		delete_option("mo_oauth_server_jwks_uri_hit_count");
		delete_option("mo_oauth_server_is_security_warning_mail_sent");
	}

	private $settings = array(
		'mo_oauth_facebook_client_secret'	=> '',
		'mo_oauth_facebook_client_id' 		=> '',
		'mo_oauth_facebook_enabled' 		=> 0
	);

	function miniorange_menu() {

		//Add miniOrange plugin to the menu
		$page = add_menu_page( 'MO OAuth Settings ' . __( 'Configure OAuth', 'mo_oauth_server_settings' ), 'miniOrange OAuth Server', 'administrator', 'mo_oauth_server_settings', array( $this, 'mo_oauth_login_options' ) ,plugin_dir_url(__FILE__) . 'images/miniorange.png');


	}

	function  mo_oauth_login_options () {
		global $wpdb;
		update_option( 'host_name', 'https://login.xecurify.com', false );
		$customerRegistered = mo_oauth_server_is_customer_registered();
		if( $customerRegistered ) {
			mo_oauth_server_page();
		} else {
			mo_oauth_server_page();
		}
	}

	function plugin_settings_style($hook) {
		wp_enqueue_style( 'mo_oauth_admin_feedback_form_settings_style', plugins_url( 'css/feedback_form_style_settings.css', __FILE__ ) );
		if($hook != 'toplevel_page_mo_oauth_server_settings') {
            return;
        }
		wp_enqueue_style( 'mo_oauth_admin_settings_style', plugins_url( 'style_settings.css', __FILE__ ), array(), '3.0.0');
		wp_enqueue_style( 'mo_oauth_admin_settings_phone_style', plugins_url( 'phone.css', __FILE__ ) );
		wp_enqueue_style( 'mo_oauth_admin_font_awesome', plugins_url( 'css/font-awesome.css', __FILE__ ) );
	}

	function plugin_settings_script($hook) {
		if($hook != 'toplevel_page_mo_oauth_server_settings') {
            return;
        }
		wp_enqueue_script( 'mo_oauth_admin_settings_script', plugins_url( 'settings.js', __FILE__ ) );
		wp_enqueue_script( 'mo_oauth_admin_settings_phone_script', plugins_url('phone.js', __FILE__ ) );
		if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'licensing'){
            wp_enqueue_script( 'mo_oauth_modernizr_script', plugins_url( 'js/modernizr.js', __FILE__ ) );
            wp_enqueue_script( 'mo_oauth_popover_script', plugins_url( 'js/bootstrap/popper.min.js', __FILE__ ) );
            wp_enqueue_script( 'mo_oauth_bootstrap_script', plugins_url( 'js/bootstrap/bootstrap.min.js', __FILE__ ) );
        }
	}

	function mo_login_widget_text_domain() {
		load_plugin_textdomain( 'flw', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
		// to update the database on plugin update
		$this->plugin_update();
	}

	private function mo_oauth_show_success_message() {
		remove_action( 'admin_notices', array( $this, 'mo_oauth_success_message') );
		add_action( 'admin_notices', array( $this, 'mo_oauth_error_message') );
	}

	private function mo_oauth_show_error_message() {
		remove_action( 'admin_notices', array( $this, 'mo_oauth_error_message') );
		add_action( 'admin_notices', array( $this, 'mo_oauth_success_message') );
	}

	public function mo_oauth_check_empty_or_null( $value ) {
		if( ! isset( $value ) || empty( $value ) ) {
			return true;
		}
		return false;
	}

	function miniorange_oauth_save_settings(){

		if( ! session_id() ) {
			session_start(['read_and_close' => true,]);
		}

		if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_mo_server_message" ) {
			update_option( 'mo_oauth_show_mo_server_message', 1 , false);

			return;
		}

		if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "change_miniorange") {
			delete_option('host_name');
			delete_option('mo_oauth_admin_email');
			delete_option('mo_oauth_server_admin_phone');
			delete_option('mo_oauth_server_verify_customer');
			delete_option('mo_oauth_server_admin_customer_key');
			delete_option('mo_oauth_server_admin_api_key');
			delete_option('mo_oauth_server_customer_token');
			delete_option('mo_oauth_server_new_customer');
			delete_option('message');
			delete_option('mo_oauth_server_new_registration');
			delete_option('mo_oauth_server_registration_status');
			delete_option('mo_oauth_show_mo_server_message');
			return;
		}

		if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_register_customer" ) {	//register the admin to miniOrange
			//validation and sanitization
			$email = '';
			$phone = '';
			$password = '';
			$confirmPassword = '';
			$fname = '';
			$lname = '';
			$company = '';
			if( $this->mo_oauth_check_empty_or_null( $_POST['email'] ) || $this->mo_oauth_check_empty_or_null( $_POST['password'] ) || $this->mo_oauth_check_empty_or_null( $_POST['confirmPassword'] ) ) {
				update_option( 'message', 'All the fields are required. Please enter valid entries.', false);
				$this->mo_oauth_show_error_message();
				return;
			} else if( strlen( $_POST['password'] ) < 8 || strlen( $_POST['confirmPassword'] ) < 8){
				update_option( 'message', 'Choose a password with minimum length 8.', false);
				$this->mo_oauth_show_error_message();
				return;
			} else{

				$email = sanitize_email( $_POST['email'] );
				$password = sanitize_text_field(stripslashes( $_POST['password'] ));
				$confirmPassword = sanitize_text_field(stripslashes( $_POST['confirmPassword'] ));
				$fname = sanitize_text_field(stripslashes( $_POST['fname'] ));
				$lname = sanitize_text_field(stripslashes( $_POST['lname'] ));
				$company = sanitize_text_field(stripslashes( $_POST['company'] ));
			}

			update_option( 'mo_oauth_admin_email', $email , false);
			update_option( 'mo_oauth_admin_fname', $fname , false);
			update_option( 'mo_oauth_admin_lname', $lname, false );
			update_option( 'mo_oauth_admin_company', $company , false);

			if( mo_oauth_server_is_curl_installed() == 0 ) {
				return $this->mo_oauth_show_curl_error();
			}

			if( strcmp( $password, $confirmPassword) == 0 ) {
				$email = get_option('mo_oauth_admin_email');
				update_option( 'password', $password, false );
				$customer = new Mo_Auth_Server_Customer();
				$content = json_decode($customer->check_customer(), true);

				if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ) {
					$response = json_decode($customer->create_customer(), true);
					if(strcasecmp($response['status'], 'SUCCESS') == 0) {
						$content = $customer->get_customer_key();
						$customerKey = json_decode( $content, true );
							if( json_last_error() == JSON_ERROR_NONE ) {
								update_option( 'mo_oauth_server_admin_customer_key', $customerKey['id'], false );
								update_option( 'mo_oauth_server_admin_api_key', $customerKey['apiKey'] , false);
								update_option( 'mo_oauth_server_customer_token', $customerKey['token'] , false);
								update_option( 'mo_oauth_server_admin_phone', $customerKey['phone'] , false);
								delete_option( 'password' );
								update_option( 'message', 'Customer created & retrieved successfully', false);
								delete_option( 'mo_oauth_server_verify_customer' );
								$this->mo_oauth_show_success_message();
							}
						wp_redirect( admin_url( '/admin.php?page=mo_oauth_server_settings&tab=login' ), 301 );
						exit;
					} else {
						update_option( 'message', 'Failed to create customer. Try again.', false);
					}
					$this->mo_oauth_show_success_message();
				} elseif(strcasecmp( $content['status'], 'SUCCESS') == 0 ) {
					update_option( 'message', 'Account already exist. Please Login.', false);
				} else {
					update_option( 'message', $content['status'] , false);
				}
				$this->mo_oauth_show_success_message();

			} else {
				update_option( 'message', 'Passwords do not match.', false);
				delete_option('mo_oauth_server_verify_customer');
				$this->mo_oauth_show_error_message();
			}
		}

		if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_oauth_register"){
			update_option('goto_registration', true, false);
			return;
		}

		if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_goto_login" ) {
			delete_option( 'mo_oauth_server_new_registration');
			update_option( 'mo_oauth_server_verify_customer', 'true' , false);
		}

		if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_verify_customer" ) {	//register the admin to miniOrange

			if( mo_oauth_server_is_curl_installed() == 0 ) {
				return $this->mo_oauth_show_curl_error();
			}
			//validation and sanitization
			$email = '';
			$password = '';
			if( $this->mo_oauth_check_empty_or_null( $_POST['email'] ) || $this->mo_oauth_check_empty_or_null( $_POST['password'] ) ) {
				update_option( 'message', 'All the fields are required. Please enter valid entries.', false);
				$this->mo_oauth_show_error_message();
				return;
			} else {
				$email = sanitize_email( $_POST['email'] );
				$password = sanitize_text_field(stripslashes( $_POST['password'] ));
			}

			update_option( 'mo_oauth_admin_email', $email , false);
			update_option( 'password', $password , false);
			$customer = new Mo_Auth_Server_Customer();
			$content = $customer->get_customer_key();
			$customerKey = json_decode( $content, true );
			if( json_last_error() == JSON_ERROR_NONE ) {
				update_option( 'mo_oauth_server_admin_customer_key', $customerKey['id'] , false);
				update_option( 'mo_oauth_server_admin_api_key', $customerKey['apiKey'], false );
				update_option( 'mo_oauth_server_customer_token', $customerKey['token'] , false);
				update_option( 'mo_oauth_server_admin_phone', $customerKey['phone'] , false);
				delete_option( 'password' );
				update_option( 'message', 'Customer retrieved successfully', false);
				delete_option( 'mo_oauth_server_verify_customer' );
				$this->mo_oauth_show_success_message();
			} else {
				update_option( 'message', 'Invalid username or password. Please try again.', false);
				$this->mo_oauth_show_error_message();
			}
		} else if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_add_client" ) {
			$clientid = '';
			$clientsecret = '';
			if($this->mo_oauth_check_empty_or_null($_POST['mo_oauth_custom_client_name']) || $this->mo_oauth_check_empty_or_null($_POST['mo_oauth_client_redirect_url'])) {
				update_option( 'message', 'Please enter valid Client Name and Redirect URI.', false);
				$this->mo_oauth_show_error_message();
				return;
			} else{
				$client_name = sanitize_text_field( $_POST['mo_oauth_custom_client_name'] );
				$redirect_url = sanitize_text_field( $_POST['mo_oauth_client_redirect_url'] );
				if ( empty( $client_name ) || empty( $redirect_url ) ) {
					update_option( 'message', 'Please enter valid Client Name and Redirect URI.', false);
					$this->mo_oauth_show_error_message();
					return;
				}
				$active_oauth_server_id = get_current_blog_id();
				$moOauthServerDb = new MoOauthServerDb();
				$clientlist = $moOauthServerDb->get_clients();
				if(sizeof($clientlist) < 1) {
					$is_client_secret_encrypted = 1;
					update_option('mo_oauth_server_is_client_secret_encrypted', $is_client_secret_encrypted, false);
					$client_secret = mo_oauth_server_encrypt( moosGenerateRandomString(32), $client_name );

					$moOauthServerDb->add_client($client_name, $client_secret, $redirect_url, $active_oauth_server_id);
					update_option( 'message', 'Your settings are saved successfully.' , false);
					update_option('mo_oauth_server_client',sanitize_text_field($_POST['client']), false);
					$this->mo_oauth_show_success_message();
				}
				else {
					update_option( 'message', 'You can add only 1 client with free version.' , false);
					$this->mo_oauth_show_error_message();
				}
			}
		}
		else if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_update_client" ) {
			$clientid = '';
			$clientsecret = '';
			if($this->mo_oauth_check_empty_or_null($_POST['mo_oauth_custom_client_name']) || $this->mo_oauth_check_empty_or_null($_POST['mo_oauth_client_redirect_url'])) {
				update_option( 'message', 'Please enter valid Client Name and Redirect URI.', false);
				$this->mo_oauth_show_error_message();
				return;
			} else{
				$client_name = sanitize_text_field(stripslashes( $_POST['mo_oauth_custom_client_name'] ));
				$redirect_url = sanitize_text_field(stripslashes( $_POST['mo_oauth_client_redirect_url'] ));
				$moOauthServerDb = new MoOauthServerDb();
				$moOauthServerDb->update_client($client_name, $redirect_url);
				update_option( 'message', 'Your settings are saved successfully.', false );
				$this->mo_oauth_show_success_message();
			}
		} else if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_oauth_server_master_switch") {
			if(!isset($_POST['mo_server_master_switch'])) {
				update_option('mo_oauth_server_master_switch', 'off', false);
			} else {
				update_option('mo_oauth_server_master_switch', 'on', false);
			}
			update_option( 'message', 'Your settings are saved successfully.', false );
			$this->mo_oauth_show_success_message();
			wp_redirect('admin.php?page=mo_oauth_server_settings&tab=general_settings');
		} else if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_oauth_general_settings") {
			if(isset($_POST['mo_server_token_length'])) {
				update_option('mo_oauth_server_token_length', (int) sanitize_text_field(stripslashes($_POST["mo_server_token_length"])), false);
			}

			if(isset($_POST['expiry_time'])) {
				update_option( 'mo_oauth_expiry_time', sanitize_text_field(intval(($_POST['expiry_time']))), false);
			}

			if(isset($_POST['refresh_expiry_time'])) {
				update_option( 'mo_oauth_refresh_expiry_time', sanitize_text_field(intval(($_POST['refresh_expiry_time']))), false);
			}

			update_option( 'message', 'Your settings are saved successfully.', false );
			$this->mo_oauth_show_success_message();
			wp_redirect('admin.php?page=mo_oauth_server_settings&tab=general_settings');
		} else if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_oauth_server_enforce_state") {
			if(!isset($_POST['mo_server_enforce_state'])) {
				update_option('mo_oauth_server_enforce_state', 'off', false);
			} else {
				update_option('mo_oauth_server_enforce_state', 'on', false);
			}

			update_option( 'message', 'Your settings are saved successfully.', false );
			$this->mo_oauth_show_success_message();
			wp_redirect('admin.php?page=mo_oauth_server_settings&tab=general_settings');
		} else if(isset($_POST['option']) and sanitize_text_field($_POST['option']) == "mo_oauth_server_enable_oidc") {
			if(!isset($_POST['mo_server_enable_oidc'])) {
				update_option('mo_oauth_server_enable_oidc', 'off', false);
			} else {
				update_option('mo_oauth_server_enable_oidc', 'on', false);
			}

			update_option( 'message', 'Your settings are saved successfully.', false );
			$this->mo_oauth_show_success_message();
			wp_redirect('admin.php?page=mo_oauth_server_settings&tab=openid_support');
		} else if(isset($_POST['option']) and $_POST['option'] == "mo_oauth_server_enable_jwt_support") {
			if(!isset($_POST['mo_oauth_server_appname'])) {
				update_option( 'message', 'There was an error saving configuration, please try again.' );
				$this->mo_oauth_show_success_message();
				wp_redirect('admin.php?page=mo_oauth_server_settings&tab=configs');
			}
			$client_name = str_replace(" ", "_", $_POST['mo_oauth_server_appname']);
			if(!isset($_POST["mo_server_enable_jwt_support_for_$client_name"])) {
				update_option('mo_oauth_server_enable_jwt_support_for_'.$client_name, 'off');
			} else {
				update_option('mo_oauth_server_enable_jwt_support_for_'.$client_name, 'on');
			}

			if(isset($_POST['mo_oauth_server_jwt_signing_algo_for_'.$client_name])) {

				$is_changed = false;
				$previous_setting = get_option('mo_oauth_server_jwt_signing_algo_for_'.$client_name) ? get_option('mo_oauth_server_jwt_signing_algo_for_'.$client_name) : false;
				if($previous_setting !== $_POST['mo_oauth_server_jwt_signing_algo_for_'.$client_name]) {
					update_option('mo_oauth_server_jwt_signing_algo_for_'.$client_name, stripslashes($_POST['mo_oauth_server_jwt_signing_algo_for_'.$client_name]));
					$current_config = stripslashes($_POST['mo_oauth_server_jwt_signing_algo_for_'.$client_name]);
					$algo = explode("S", $current_config);
					$sha = $algo[1];
					global $wpdb;
					$myrows = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."moos_oauth_clients WHERE client_name = '".$_POST['mo_oauth_server_appname']."' and active_oauth_server_id=".get_current_blog_id());
					$client_exists = $wpdb->query("SELECT * FROM ".$wpdb->base_prefix."moos_oauth_public_keys WHERE client_id = '".$myrows[0]->client_id."'");
					if(!$client_exists) {
						$result = $wpdb->query("INSERT INTO ".$wpdb->base_prefix."moos_oauth_public_keys (client_id, public_key, private_key, encryption_algorithm) VALUES ('".$myrows[0]->client_id."', '', '', 'HS256')");
					}
					if($algo[0] === "R") {
						// Generate New Key Pair for New Client

						$privatekey = "-----BEGIN RSA PRIVATE KEY-----\n".
						"MIIJKQIBAAKCAgEA4TVk77HWIUc5KdUQXufr2eXuKMN6PfScl+KjxDF/41xdyXu/\n".
						"KG+dxIzz3jp/dZCdGHEHWQQx4LT1fNTwSp5h33tKNn5gaL/UspjMC468Hj9uDYN+\n".
						"XPhkNTqGQLmzRijAwH+Sqm4sM3GWwvlKm44QBEIXTgRaKripEFu70imV6CFKX4fm\n".
						"FqD2O1bhXg6LNpZX8lOLKwUBS6VMmNQTnxFlyRapiCcTQbvZPHG6LO7KiRcqPcm6\n".
						"qZFf2p+ogSEGOw71hcU7bW9ZLK6wMF/g03Azj+eWgcxGGIDUxlrv4hCXuFuzJEJ5\n".
						"sBbjjRsZ815ri9xSYAF3njgvuETbHA59/nvzfl8EPI2DYQsAIYTxnd00ZWXAcbRA\n".
						"EgxRsx1eJIFQMxp7Qg+jLr0n0OLQ29uGNiwK8NNJZbkEdm4ORfULIBVx3a5Bm1ad\n".
						"zTORUIe3a+xVdLBlRyurRB28DaTJBbTPEE4wCLnjtoSvnTvX0EZFozIrjYm3uVUl\n".
						"CDVY9DQT54mfRGrpuflvDK909+qlnTKedNcdoBzsh07QneFiZF0kQ5YOivz4GF1K\n".
						"6QFYHBOWpLQ2mYRzJkwMgaJGDlBrZvTup0iWnRCwQHPajYKQ6YiCEQ9gaelEDoG9\n".
						"v0ijzCGmKI5UJ9CNegcTrojiVLrGKg3M0cebtsPraAZdREqAXzLY3LUdQ3ECAwEA\n".
						"AQKCAgEAi6StEselq/ra4iqAPSj3oKQnGdWj7djIZJGe1F+RGizC5tU5gdw76o0w\n".
						"BgMl14M1NduYH8UvHFN4yM/Ms6gjrgxnxwRzyV/xhlCibSQzV1ojZnO7nfBTSoIg\n".
						"ju/WztEkO/ieu9kWxUtQnVMwxOXA3rMQekrOkiDwi/klrDom/sntsPC2Zh+mrsK/\n".
						"ea/w+Icev164M5Ol6v7zUOxnwkFqaNcJhigck6zkFcu7EnN62Kipg6ibettuoURQ\n".
						"mskccPBko27Z25CorcEf9M7uvIydHEUQmSlN6ZGw3dGzXuzE9wa9POWPSPXYYT2F\n".
						"yNcqPo398hPW1R+nz026w1nlHDBFmVL2TsVCPz5vGxQ47YRoQ92Z4JnqxcnOADqX\n".
						"mth8o9iiUDwk0ZRtKCuF1i8n82i7Pl1vR6wHnaRii8QyvfhauDdSJ70lNA/O/wm5\n".
						"S8+dctFLVLPDGfWa5k1BO5iB06j652IH2WEL/dDZNuaa/gEPMReyong5hpIBp/Dw\n".
						"KltNGx5+ogDLcpVcrtwQZwbzgfznoY14n9zikZQ4L4jbRNCFxOhHYuiIyXkgfpg7\n".
						"ouNXQma1vdLw9rLB3e92SS7Zirk8a8cn491MlxcmP8l2OWH8t4CQrHEpHJu3tO0o\n".
						"9X6Ay7iKxSm3SjbG4IBzNA5FJ7Km/FZH5mFJMmxxN3PPGIMYxiUCggEBAP/xXbE0\n".
						"E6bSd+OC4K4GduohxSRbMEnukKkTuKknRL63IhQZ0bgtCaVvRX5guPbasgZgAa9z\n".
						"6KMOSnmXQAoCULekW4tiPKLDbARhiSHSUASI7SsxiYbALGt+xrPSFhUal45FI4AU\n".
						"4fyqQ8sI0OiLC1mmu6nLYgqW+NgkOfZQc4BWPdNtOoz17VPabtZXj6RrpFjT13Xt\n".
						"e/yKnl15LNIs3PpooS8vSkxul1zyK/lPVLXVevxomyz1xL4H/SpRfZYOvd9wU6xF\n".
						"vwp4YuM4enRjtF+yPUyvSa2uEYfw0ke0o9f2iK5cndLkOb15hlCxeyx+l09Ebo9f\n".
						"69BJvW3TX5btFrsCggEBAOFCRWDCqKm+VnkIXvH/kg4LexeOJlccgjl6W+sP6bhd\n".
						"M52juRM5jp7LBuOEiPpB1VonFtH8sjr05Nr76mgzUNn/sAVPTOAiwBCaiVLXiymT\n".
						"gIruWaovq4Y8ulpqbutfqZ9JTjnluUHROoLZKB006/QofNXkVnkGBMrUK/m7PlTl\n".
						"t8J2i5EkYZ1H24E/Ek0WRX2Qap3pycm3yqDylga5SJKBlg/G4GtOlGBMMYZlB+bp\n".
						"sLl55XCA31g3OSaagdGUuR0OFon66/21t0ZR2r3MbOGk78sRNR91gexN3FYeVTmu\n".
						"XEsJIBTqYrwC6UpbWXrS1+vQwY3ShBRpsqCcteITKcMCggEAN3+cJGe5dyweSRxB\n".
						"IhtOv9hQymBnqTBs9+zJ1wwn0P8fCaLLohdKBzCIri3FDepAPjelRelpYaogphsR\n".
						"DNqRrDCclS3ZHiYoDw8jUE0tgr46R2p3etvDBhA4gBenKC5a/MOrPgPJOSOmjak8\n".
						"u6Ai9u67tMbgXJF+Jkg8tVeepA6PW4BM+PH+43bzH9Fe2XVp7sUI7I5xm0Jnsrcq\n".
						"6+xEgpwbj4K+prI4ajQtKuNz5/YBtCfutiIY5mgPEpUXGWna7E+MJUf+dAPE1aaS\n".
						"jxhrrXCV8EH2RQ4AySyEPH5EJPlVjBGTO363solegbLqlaxhnROmsbpIBSNoSx9R\n".
						"lAWXLwKCAQEAoSnSC3WaSL/2jGfRzmCk9cl/Cw5YHhE2lrsVkqty88YzDME7xCZ1\n".
						"BOWLizKi8jIx3GuFJz4dopLePlLolh7I5P/LxzDCdsZGFlsKjyvJ1DhFSqFXo6yx\n".
						"krxWNCRcMaji6iT/g+r5Tb7NlxqZWbQocSqajkntGG+W9CszP1yZLxKgE9DO8ExQ\n".
						"TsA/q0wd4uthUoIF1e+TwO/vWJHXhv3/j1qJq8YFgKDbBb7d3CLisXJXT4yH/KMn\n".
						"qKzyBc2bvgAjJUeUFqphN8dQVk5wK0VcTWC9c9Ne56AiEZhvYWoYXcmDHOhtfKlp\n".
						"dMy8bsfG0FqTw5M7OCX6+8PX2pPkidheEwKCAQBVo4HxuVvgSCHiNuCtH5ZIWpQa\n".
						"mWqYae+FSKe1ifO67HTV4v6j/rQ8y2mtWIhhyfTPW+rxZiTuPyL+r+Nmo7t1Jt4w\n".
						"HA1/4SOfI/8emwWQYD1T3SCouSa0rzoOPN7KT8l9qBGhAOC7u5j3DNMK+nxVT9fr\n".
						"3dkGiHURLFTCa/9wzJI1oVa7ttE8WLnWbLZPRDEYrB80HoWBXUmVxrs/JieE90LW\n".
						"/wZ/BZ4eyS95JV/irfw38ewUq1EPelTbhOOdqSood1s7wwockLdPavpSjlvJCdhT\n".
						"cA7/EdDA1a5ARtAe14963aKEJAozxE4neD1BX5a5qP+vbCBpAgxWr77Jwy7N\n".
						"-----END RSA PRIVATE KEY-----";
						$publickey  = "-----BEGIN PUBLIC KEY-----\n".
						"MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA4TVk77HWIUc5KdUQXufr\n".
						"2eXuKMN6PfScl+KjxDF/41xdyXu/KG+dxIzz3jp/dZCdGHEHWQQx4LT1fNTwSp5h\n".
						"33tKNn5gaL/UspjMC468Hj9uDYN+XPhkNTqGQLmzRijAwH+Sqm4sM3GWwvlKm44Q\n".
						"BEIXTgRaKripEFu70imV6CFKX4fmFqD2O1bhXg6LNpZX8lOLKwUBS6VMmNQTnxFl\n".
						"yRapiCcTQbvZPHG6LO7KiRcqPcm6qZFf2p+ogSEGOw71hcU7bW9ZLK6wMF/g03Az\n".
						"j+eWgcxGGIDUxlrv4hCXuFuzJEJ5sBbjjRsZ815ri9xSYAF3njgvuETbHA59/nvz\n".
						"fl8EPI2DYQsAIYTxnd00ZWXAcbRAEgxRsx1eJIFQMxp7Qg+jLr0n0OLQ29uGNiwK\n".
						"8NNJZbkEdm4ORfULIBVx3a5Bm1adzTORUIe3a+xVdLBlRyurRB28DaTJBbTPEE4w\n".
						"CLnjtoSvnTvX0EZFozIrjYm3uVUlCDVY9DQT54mfRGrpuflvDK909+qlnTKedNcd\n".
						"oBzsh07QneFiZF0kQ5YOivz4GF1K6QFYHBOWpLQ2mYRzJkwMgaJGDlBrZvTup0iW\n".
						"nRCwQHPajYKQ6YiCEQ9gaelEDoG9v0ijzCGmKI5UJ9CNegcTrojiVLrGKg3M0ceb\n".
						"tsPraAZdREqAXzLY3LUdQ3ECAwEAAQ==\n".
						"-----END PUBLIC KEY-----";

						$result = $wpdb->query("UPDATE ".$wpdb->base_prefix."moos_oauth_public_keys SET public_key = '".$publickey."', private_key = '".$privatekey."', encryption_algorithm = '".$current_config."' WHERE client_id = '".$myrows[0]->client_id."'");
					} else {
						$result = $wpdb->query("UPDATE ".$wpdb->base_prefix."moos_oauth_public_keys SET public_key = '', private_key = '".$myrows[0]->client_secret."', encryption_algorithm = '".$current_config."' WHERE client_id = '".$myrows[0]->client_id."'");
					}
				}
			}

			update_option( 'message', 'Your settings are saved successfully.' );
			$this->mo_oauth_show_success_message();
			wp_redirect('admin.php?page=mo_oauth_server_settings&tab=configs&action=update&client='.str_replace("_", "+", $client_name));
		}

		// Trials Available
		elseif( isset( $_POST['option'] ) and sanitize_text_field( wp_unslash( $_POST['option'] ) ) == "mo_oauth_server_demo_request_form" && isset($_REQUEST['mo_oauth_server_demo_request_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['mo_oauth_server_demo_request_field'])), 'mo_oauth_server_demo_request_form') ) {
			if( current_user_can( 'administrator' ) ) {
				if( mo_oauth_server_is_curl_installed() == 0 ) {
					return $this->mo_oauth_show_curl_error();
				}
				// Demo Request
				$email = sanitize_email( $_POST['mo_auto_create_demosite_email'] );
				$demo_plan = stripslashes( sanitize_text_field($_POST['mo_auto_create_demosite_demo_plan']) );
				$query = stripslashes( sanitize_text_field($_POST['mo_auto_create_demosite_usecase']) );

				if ( $this->mo_oauth_check_empty_or_null( $email ) || $this->mo_oauth_check_empty_or_null( $demo_plan ) || $this->mo_oauth_check_empty_or_null($query) ) {
					update_option('message', 'Please fill up Usecase, Email field and Requested demo plan to submit your query.');
					$this->mo_oauth_show_error_message();
				} else {
					$url = 'https://demo.miniorange.com/wposerver/';

					$headers = array( 'Content-Type' => 'application/x-www-form-urlencoded', 'charset' => 'UTF - 8');
					$args = array(
					'method' =>'POST',
					'body' => array(
					'option' => 'mo_auto_create_demosite',
					'mo_auto_create_demosite_email' => $email,
					'mo_auto_create_demosite_usecase' => $query,
					'mo_auto_create_demosite_demo_plan' => $demo_plan
					),
					'timeout' => '20',
					'redirection' => '5',
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $headers,
					);

					$response = wp_remote_post( $url, $args );

					if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					echo "Something went wrong: $error_message";
					exit();
					}
					$output = wp_remote_retrieve_body($response);
					$output = json_decode($output);
					if(is_null($output)){
						update_option('message', 'Something went wrong! contact to your administrator');
						$this->mo_oauth_show_success_message();
					}
					if($output->status == 'SUCCESS'){
						update_option('message', $output->message);
						$this->mo_oauth_show_success_message();
						
						// notify the user via email for demo set-up
						$customer = new Mo_Auth_Server_Customer();
						$customer->mo_oauth_send_demo_alert( $email, $demo_plan, $query, "WP OAuth Server On Demo Request - ".$email );

					} else {
						update_option('message', $output->message);
						$this->mo_oauth_show_error_message();
					}
				}
			}
		}
		
		// video demo
		elseif( isset( $_POST['option'] ) and sanitize_text_field( wp_unslash( $_POST['option'] ) ) == "mo_oauth_server_video_demo_request_form" && isset($_REQUEST['mo_oauth_server_video_demo_request_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['mo_oauth_server_video_demo_request_field'])), 'mo_oauth_server_video_demo_request_form') ) {
			if( current_user_can( 'administrator' ) ) {
				if( mo_oauth_server_is_curl_installed() == 0 ) {
					return $this->mo_oauth_show_curl_error();
				}

				//video demo request
				$email = sanitize_email( $_POST['mo_oauth_video_demo_email'] );
				$call_date = isset($_POST['mo_oauth_video_demo_request_date']) ? sanitize_text_field($_POST['mo_oauth_video_demo_request_date']) : '';
				$time_diff = isset($_POST['mo_oauth_video_demo_time_diff']) ? sanitize_text_field($_POST['mo_oauth_video_demo_time_diff']) : '';	//timezone offset
				$call_time = isset($_POST['mo_oauth_video_demo_request_time']) ? sanitize_text_field($_POST['mo_oauth_video_demo_request_time']) : ''; //time input
				$query = stripslashes( sanitize_text_field($_POST['mo_oauth_video_demo_request_usecase_text']) );
				$customer = new Mo_Auth_Server_Customer();

				if ( $this->mo_oauth_check_empty_or_null( $email ) || $this->mo_oauth_check_empty_or_null( $call_date ) || $this->mo_oauth_check_empty_or_null($query) || $this->mo_oauth_check_empty_or_null( $time_diff ) || $this->mo_oauth_check_empty_or_null( $call_time ) ) {
					update_option('message', 'Please fill up Usecase, Email field and Requested demo plan to submit your query.');
					$this->mo_oauth_show_error_message();
				} else {
					$mo_oauth_video_demo_request_validated = false;
					$email = sanitize_email( $_POST['mo_oauth_video_demo_email'] );
					$call_date = isset($_POST['mo_oauth_video_demo_request_date']) ? sanitize_text_field($_POST['mo_oauth_video_demo_request_date']) : '';
					$time_diff = isset($_POST['mo_oauth_video_demo_time_diff']) ? sanitize_text_field($_POST['mo_oauth_video_demo_time_diff']) : '';	//timezone offset
					$call_time = isset($_POST['mo_oauth_video_demo_request_time']) ? sanitize_text_field($_POST['mo_oauth_video_demo_request_time']) : ''; //time input
					$query = "[WordPress WP OAuth Server free] : " . stripslashes( sanitize_text_field($_POST['mo_oauth_video_demo_request_usecase_text']) );
						
					if ( !($this->mo_oauth_check_empty_or_null( $email ) || $this->mo_oauth_check_empty_or_null( $query ) || $this->mo_oauth_check_empty_or_null( $call_date ) || $this->mo_oauth_check_empty_or_null( $time_diff ) || $this->mo_oauth_check_empty_or_null( $call_time )) ) {
						// Modify the $time_diff to test for the different timezones.
						// Note - $time_diff for IST is -330
						// $time_diff = 240;
						$hrs = floor(abs($time_diff)/60);
							$mins = fmod(abs($time_diff),60);
							if($mins == 0) {
								$mins = '00';
							}
							$sign = '+';
							if($time_diff > 0) {
								$sign = '-';
							}
							$call_time_zone = 'UTC '.$sign.' '.$hrs.':'.$mins;
							$call_date = date("jS F",strtotime($call_date));
							
							//code to convert local time to IST
							$local_hrs = explode(':', $call_time)[0];
							$local_mins = explode(':', $call_time)[1];
							$call_time_mins = ($local_hrs * 60) + $local_mins;
							$ist_time = $call_time_mins + $time_diff + 330;
							$ist_date = $call_date;
							if($ist_time > 1440) {
								$ist_time = fmod($ist_time,1440);
								$ist_date = date("jS F", strtotime("1 day", strtotime($call_date)));
							}
							else if($ist_time < 0) {
								$ist_time = 1440 + $ist_time;
								$ist_date = date("jS F", strtotime("-1 day", strtotime($call_date)));
							}
							$ist_hrs = floor($ist_time/60);
							$ist_hrs = sprintf("%02d", $ist_hrs);

							$ist_mins = fmod($ist_time,60);
							$ist_mins = sprintf("%02d", $ist_mins);
							
							$ist_time = $ist_hrs.':'.$ist_mins;
							$mo_oauth_video_demo_request_validated = true;
					}


					if ($mo_oauth_video_demo_request_validated) {
						$customer->mo_oauth_send_video_demo_alert( $email, $ist_date, $query,$ist_time, "WP OAuth Server Video Demo Request - ".$email ,$call_time_zone,$call_time,$call_date);
						update_option('message', "Thanks for getting in touch! We shall get back to you shortly.");
						$this->mo_oauth_show_success_message();
					}
					else {
						update_option('message', 'Your query could not be submitted. Please fill up all the required fields and try again.');
						$this->mo_oauth_show_error_message();
					} 
				}
			}
		}
		
		elseif( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_contact_us_query_option" ) {
			if( mo_oauth_server_is_curl_installed() == 0 ) {
				return $this->mo_oauth_show_curl_error();
			}
			// Contact Us query
			$email = sanitize_email(stripslashes($_POST['mo_oauth_contact_us_email']));
			$phone = sanitize_text_field(stripslashes($_POST['mo_oauth_contact_us_phone']));
			$query = sanitize_text_field(stripslashes($_POST['mo_oauth_contact_us_query']));

			if(array_key_exists('mo_idp_upgrade_plan_name',$_POST)) {
				$plan_name 	= sanitize_text_field($_POST['mo_idp_upgrade_plan_name']);
				$plan_users = sanitize_text_field($_POST['mo_idp_upgrade_plan_users']);
				$query = "Plan Name : ".esc_attr($plan_name).", Users : ".esc_attr($plan_users).", ".esc_attr($query);
			}

			$customer = new Mo_Auth_Server_Customer();
			if ( $this->mo_oauth_check_empty_or_null( $email ) || $this->mo_oauth_check_empty_or_null( $query ) ) {
				update_option('message', 'Please fill up Email and Query fields to submit your query.', false);
				$this->mo_oauth_show_error_message();
			} else {
				$submited = $customer->submit_contact_us( $email, $phone, $query );
				if ( $submited == false ) {
					update_option('message', 'Your query could not be submitted. Please try again.', false);
					$this->mo_oauth_show_error_message();
				} else {
					update_option('message', 'Thanks for getting in touch! We shall get back to you shortly.', false);
					$this->mo_oauth_show_success_message();
				}
			}
		}else if( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == "mo_oauth_change_phone" ) {
			update_option('mo_oauth_server_registration_status','', false);
		}
		if ( isset( $_POST['option'] ) and sanitize_text_field($_POST['option']) == 'mo_oauth_server_skip_feedback' ) {
			deactivate_plugins( __FILE__ );
			update_option( 'message', 'Plugin deactivated successfully' , false);
			$this->mo_oauth_show_success_message();
		} else if ( isset( $_POST['mo_oauth_server_feedback'] ) and $_POST['mo_oauth_server_feedback'] == 'true' ) {
			$user = wp_get_current_user();
			$message = 'Plugin Deactivated:';
			$deactivate_reason         = array_key_exists( 'deactivate_reason_radio', $_POST ) ? sanitize_text_field($_POST['deactivate_reason_radio']) : false;
			$deactivate_reason_message = array_key_exists( 'query_feedback', $_POST ) ? sanitize_text_field($_POST['query_feedback']) : false;
			if ( $deactivate_reason ) {
				$message .= $deactivate_reason;
				if ( isset( $deactivate_reason_message ) ) {
					$message .= ':' . $deactivate_reason_message;
				}
				$email = get_option( "mo_oauth_admin_email" );
				if ( $email == '' ) {
					$email = $user->user_email;
				}
				$phone = get_option( 'mo_oauth_server_admin_phone' );
				//only reason
				$feedback_reasons = new Mo_Auth_Server_Customer();
				$submited = json_decode( $feedback_reasons->mo_oauth_send_email_alert( $email, $phone, $message ), true );
				deactivate_plugins( __FILE__ );
				update_option( 'message', 'Thank you for the feedback.' );
				$this->mo_oauth_show_success_message();
			} else {
				update_option( 'message', 'Please Select one of the reasons ,if your reason is not mentioned please select Other Reasons' );
				$this->mo_oauth_show_error_message();
			}
		}
		
		//Download Sample JSON
		else if(isset($_REQUEST['option']) and $_REQUEST['option'] == 'downloadsamplejson'){
			global $wpdb;
			$client_id = isset($_REQUEST['client']) ? stripslashes( $_REQUEST['client'] ) : false;
			if ( false === $client_id ) {
				wp_die( 'Invalid Client.' );
			}

			$client = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."moos_oauth_clients where client_id = '".$client_id."';", ARRAY_A);
			
			$client_secret = mo_oauth_server_decrypt( $client['client_secret'], $client['client_name'] );

			$file_path = __DIR__ .DIRECTORY_SEPARATOR. "samples" .DIRECTORY_SEPARATOR. "OAuth_Postman_Collection.json";
			
			//Read JSON file
			$sample_json = file_get_contents($file_path, "OAuth_Postman_Collection.json");
			//Decode JSON file
			$json_data = json_decode("$sample_json", true);


			$info = $json_data['info'];
			$item = $json_data['item'];

			$access_token_url = site_url()."/wp-json/moserver/token";
			$auth_url = site_url()."/wp-json/moserver/authorize";
			$resource_url = site_url()."/wp-json/moserver/resource";
			$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
			$path_url = explode('/', $_SERVER['REQUEST_URI']);
			$path = "";
			$i=1;
			while($path_url[$i]!='wp-admin'){
				$path = $path."/".$path_url[$i];
				$i++;
			}
			$path = trim($path, "/");

			$item[0]['request']['auth']['oauth2'][0]['value'] = $client_secret;
			$item[0]['request']['auth']['oauth2'][1]['value'] = $client_id;
			$item[0]['request']['auth']['oauth2'][3]['value'] = $access_token_url;
			$item[0]['request']['auth']['oauth2'][6]['value'] = $auth_url;
			$item[0]['request']['auth']['oauth2'][8]['value'] = $client['client_name'];
			$item[0]['request']['url']['raw'] = $resource_url;
			$item[0]['request']['url']['protocol'] = $protocol;
			$item[0]['request']['url']['host'] = $_SERVER['SERVER_NAME'];
			$item[0]['request']['url']['path'][0] = $path;

			$json_data['item'] = $item;

			$new_json_data = json_encode($json_data);
			
			header('Content-Disposition: attachment; filename="Sample_JSON.json"');
			header('Content-Type: text/plain');
			header('Content-Length: ' . strlen($new_json_data));
			header('Connection: close');

			echo $new_json_data;
			exit();
		}  else if(isset($_REQUEST['option']) and $_REQUEST['option'] == 'downloadsigningcertificate'){
			global $wpdb;
			$client_id = isset($_REQUEST['client']) ? stripslashes( $_REQUEST['client'] ) : false;
			if ( false === $client_id ) {
				wp_die( 'Invalid Client.' );
			}
        	$public_key = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."moos_oauth_public_keys where client_id = '".$client_id."';", ARRAY_A)['public_key'];
			header('Content-Disposition: attachment; filename="pubKey.pem"');
			header('Content-Type: text/plain');
			header('Content-Length: ' . strlen($public_key));
			header('Connection: close');

			echo $public_key;
			exit();
		}
	}



	function mo_oauth_get_current_Mo_Auth_Server_Customer(){
		$customer = new Mo_Auth_Server_Customer();
		$content = $customer->get_customer_key();
		$customerKey = json_decode( $content, true );
		if( json_last_error() == JSON_ERROR_NONE ) {
			update_option( 'mo_oauth_server_admin_customer_key', $customerKey['id'], false );
			update_option( 'mo_oauth_server_admin_api_key', $customerKey['apiKey'], false );
			update_option( 'mo_oauth_server_customer_token', $customerKey['token'], false );
			update_option( 'password', '' , false);
			update_option( 'message', 'Customer retrieved successfully', false );
			update_option('mo_oauth_server_registration_status','MO_OAUTH_CUSTOMER_RETRIEVED', false);
			delete_option('mo_oauth_server_verify_customer');
			delete_option('mo_oauth_server_new_registration');
			$this->mo_oauth_show_success_message();
			//mo_oauth_server_page();
		} else {
			update_option( 'message', 'You already have an account with miniOrange. Please enter a valid password.', false);
			update_option('mo_oauth_server_verify_customer', 'true', false);
			delete_option('mo_oauth_server_new_registration');
			//mo_oauth_server_page();
			$this->mo_oauth_show_error_message();

		}
	}

	function create_customer(){
		$customer = new Mo_Auth_Server_Customer();
		$customerKey = json_decode( $customer->create_customer(), true );
		if( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0 ) {
			$this->mo_oauth_get_current_Mo_Auth_Server_Customer();
			delete_option('mo_oauth_server_new_customer');
		} else if( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
			update_option( 'mo_oauth_server_admin_customer_key', $customerKey['id'], false );
			update_option( 'mo_oauth_server_admin_api_key', $customerKey['apiKey'], false );
			update_option( 'mo_oauth_server_customer_token', $customerKey['token'] , false);
			update_option( 'password', '', false);
			update_option( 'message', 'Registered successfully.', false);
			update_option('mo_oauth_server_registration_status','MO_OAUTH_REGISTRATION_COMPLETE', false);
			update_option('mo_oauth_server_new_customer',1, false);
			delete_option('mo_oauth_server_verify_customer');
			delete_option('mo_oauth_server_new_registration');
			$this->mo_oauth_show_success_message();
		}
	}

	function mo_oauth_show_curl_error() {
		if( mo_oauth_server_is_curl_installed() == 0 ) {
			update_option( 'message', '<a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP CURL extension</a> is not installed or disabled. Please enable it to continue.', false);
			$this->mo_oauth_show_error_message();
			return;
		}
	}

}

	/**
	 * Encc.
	 *
	 * @param string $str String to encc.
	 */
	function mo_oauth_server_encrypt( $strSecret, $strName ) {
		$pass = $strName;
		if ( ! $pass ) {
			return 'false';
		}
		$pass = str_split( str_pad( '', strlen( $strSecret ), $pass, STR_PAD_RIGHT ) );
		$stra = str_split( $strSecret );
		foreach ( $stra as $k => $v ) {
			$tmp        = ord( $v ) + ord( $pass[ $k ] );
			$stra[ $k ] = chr( $tmp > 255 ? ( $tmp - 256 ) : $tmp );
		}
		return base64_encode( join( '', $stra ) ); // phpcs:ignore
	}
	/**
	 * Dencc.
	 *
	 * @param string $str String to dencc.
	 */
	function mo_oauth_server_decrypt( $str, $strName ) {
		// miniorange oauth server plugin update version 5 onwards
		// storing client secret in encrypted format

		$is_client_secret_encrypted = get_option('mo_oauth_server_is_client_secret_encrypted');
        
        if (!$is_client_secret_encrypted) {
            // If client secret is not encrypted encrypt it
			$moOauthServerDb = new MoOauthServerDb();
            $clientlist = $moOauthServerDb->get_clients();
			if (sizeof($clientlist) < 1) {
				echo "Client not found! Please set up client first.";
				exit();
			}
            $client_secret = $clientlist[0]->client_secret;
			$client_name = $clientlist[0]->client_name;
            $client_secret = mo_oauth_server_encrypt( $client_secret, $client_name );

			// Insert updated client secret to database
			global $wpdb;
			$sql = $wpdb->prepare("UPDATE ".$wpdb->base_prefix."moos_oauth_clients SET client_secret = %s WHERE client_name = %s and active_oauth_server_id= %d",array($client_secret, $client_name, get_current_blog_id()));
			$wpdb->query($sql);

			$is_client_secret_encrypted = 1;
			update_option('mo_oauth_server_is_client_secret_encrypted', $is_client_secret_encrypted, false);
			
			$str = $client_secret;
		}
		
		$str  = base64_decode( $str ); // phpcs:ignore

		$pass = $strName;
		if ( ! $pass ) {
			return 'false';
		}

		$pass = str_split( str_pad( '', strlen( $str ), $pass, STR_PAD_RIGHT ) );
		$stra = str_split( $str );
		foreach ( $stra as $k => $v ) {
			$tmp        = ord( $v ) - ord( $pass[ $k ] );
			$stra[ $k ] = chr( $tmp < 0 ? ( $tmp + 256 ) : $tmp );
		}
		return join( '', $stra );
	}


	function mo_oauth_server_is_customer_registered() {
		$email 			= get_option('mo_oauth_admin_email');
		$customerKey 	= get_option('mo_oauth_server_admin_customer_key');
		if( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
			return 0;
		} else {
			return 1;
		}
	}

	function mo_oauth_server_is_curl_installed() {
		if  (in_array  ('curl', get_loaded_extensions())) {
			return 1;
		} else {
			return 0;
		}
	}

	function moosGenerateRandomString($length = 10) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	/**
	 * Function to register all routes with WordPress.
	 */
	function mo_oauth_server_register_endpoints() {
	    $default_routes = mo_oauth_server_get_default_routes();
	    $new_routes = apply_filters( 'mo_oauth_server_define_routes', $default_routes );
	    if ( ! empty( $new_routes ) ) {
	        $default_routes = array_merge( $new_routes, $default_routes );
	    }
	    foreach( $default_routes as $route => $args ) {
	        register_rest_route( 'moserver', $route, $args );
	    }
	    $well_knowns = mo_get_well_known_routes();	
	    foreach( $well_knowns as $route => $args ) {	
	        register_rest_route( 'moserver', '(?P<client_id>\w+)/.well-known/' . $route, $args );	
	    }	
	}

	add_action( 'rest_api_init', 'mo_oauth_server_register_endpoints' );

new mo_oauth_server;
