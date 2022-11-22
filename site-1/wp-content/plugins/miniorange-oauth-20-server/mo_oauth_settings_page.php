<?php

function mo_oauth_server_page()
{
    if (
        isset($_SERVER["HTTP_HOST"]) &&
        strpos($_SERVER["HTTP_HOST"], "localhost") !== false
    ) {
        $class = "notice notice-warning";
        $message = 'It seems you are using this plugin on localhost. Please make sure that your site is reachable from the client site.';
        echo "<div class='" .
            esc_attr($class) .
            "'><p>" .
            esc_attr($message) .
            "</p></div>";
    }
	// Security warning notice on plugin page
	$jwks_uri_hit_count = get_option("mo_oauth_server_jwks_uri_hit_count");
	if($jwks_uri_hit_count >= 10){
		$is_security_warning_mail_sent = get_option("mo_oauth_server_is_security_warning_mail_sent");
		if($jwks_uri_hit_count == 10 && $is_security_warning_mail_sent === false ){
			$is_security_warning_mail_sent = 1;
			update_option("mo_oauth_server_is_security_warning_mail_sent", $is_security_warning_mail_sent, false);
			$email = get_option("admin_email");
			$login_url = get_option("host_name") . "/moas/login";
			$username = get_option("mo_oauth_admin_email");
			$payment_url = get_option("host_name") . "/moas/initializepayment";
				
			$message = 'Dear Customer, <br><br>

			You are at a Security Risk for the WordPress OAuth Server Plugin. It is because you are using the free version of the plugin for JWT Signing, where new keys are not generated for each configuration and are common for all users.<br><br>
			You can			
			<form style="display:inline;" id="email_loginform" action="' . esc_url_raw($login_url) . '" target="_blank" method="post">
				<input style="display:none;" type="email" name="username" value="' . esc_attr($username) . '" />
				<input style="display:none;" type="text" name="redirectUrl" value="' . esc_url_raw($payment_url) . '" />
				<input style="display:none;" type="text" name="requestOrigin" value="wp_oauth_server_enterprise_plan"  />
				<button style="border: none; background: transparent; color: blue; text-decoration: underline;" type="submit">Click here to Upgrade to Premium</button>
			</form>
			for RSA support with dynamic keys to avoid this risk.<br><br>
			<i><b>Note:</b> The free plugin will stay functional but remain subject to this risk.</i>
			<br><br>
			For more information, you can contact us at oauthsupport@xecurify.com. <br><br>

	
			Thank you,<br>
			miniOrange Team';
			$customer = new Mo_Auth_Server_Customer();
			$customer->mo_oauth_send_jwks_alert( $email, $message, "WP OAuth Server Alert | You are at a Security Risk - ".$email );
		}
		?>
		<div class="notice security-banner security-banner-plugin">
			<div class="security-banner-content">
				<div class="banner-header">
					<span class="warning-icon dashicons dashicons-warning"></span>
					<span>WARNING!</span>
				</div>
				<?php 
				$login_url = get_option("host_name") . "/moas/login";
				$username = get_option("mo_oauth_admin_email");
				$payment_url = get_option("host_name") . "/moas/initializepayment";
				echo'
				<form style="display:none;" id="loginform" action="' . esc_url_raw($login_url) . '" target="_blank" method="post">
				<input type="email" name="username" value="' . esc_attr($username) . '" />
				<input type="text" name="redirectUrl" value="' . esc_url_raw($payment_url) . '" />
				<input type="text" name="requestOrigin" value="wp_oauth_server_enterprise_plan"  />
				</form>';
			?>	
				<script>
					function upgrade_plugin_form() {
						jQuery("#loginform").submit()
					}
				</script>
				<div class="button-tab">
					<a onclick="upgrade_plugin_form()" class="button button-primary button-large">Upgrade Now</a>
				</div>
				<br>
				<p class="notice-important">You are at a Security Risk for the WordPress OAuth Server Plugin. It is because you are using the free version of the plugin for JWT Signing, where new keys are not generated for each configuration and are common for all users.</p>
				<p class="notice-important">You can Upgrade to Premium for RSA support with dynamic keys to avoid this risk.</p>
				<div class="security-banner-footer">
					<p>Contact us at <a href="mailto:oauthsupport@xecurify.com" style="text-decoration: none">oauthsupport@xecurify.com</a> for upgrading to premium or any other query.</p>
				</div>
			</div>
		</div>
	<?php
	}
    $currenttab = "";
    if (isset($_GET["tab"])) {
        $currenttab = sanitize_text_field(stripslashes($_GET["tab"]));
    }
    ?>
<?php if (mo_oauth_server_is_curl_installed() == 0) { ?>
<p style="color:red;">(Warning: <a href="http://php.net/manual/en/curl.installation.php" target="_blank" rel="noopener">PHP CURL extension</a> is not installed or disabled. Please install/enable it before you proceed.)</p>
<?php } ?>
<div class="wrap">
	<img style="float:left;" src="<?php echo plugin_dir_url(
     __FILE__
 ); ?>/images/logo.png">
</div>
<div class="wrap">
	<h1>miniOrange OAuth / OpenID Connect Server</h1>
</div>
<div id="mo_oauth_settings">
	<?php if (
     $currenttab == "licensing" ||
     !get_option("mo_oauth_show_mo_server_message")
 ) { ?>
	<form name="f" method="post" action="" id="mo_oauth_mo_server_form">
		<input type="hidden" name="option" value="mo_oauth_mo_server_message" />
		<div class="notice notice-info" style="padding-right: 38px;position: relative;">
			<h4><?php _e(
       "Looking for a User Storage/OAuth Server? We have a B2C Service(Cloud IDP) which can scale to hundreds of millions of consumer identities. You can",
       "miniorange-login-with-eve-online-google-facebook"
   ); ?> <a href="https://idp.miniorange.com/b2c-pricing" target="_blank" rel="noopener"><?php _e(
     "click here",
     "miniorange-login-with-eve-online-google-facebook"
 ); ?></a> <?php _e(
    "to find more about it.",
    "miniorange-login-with-eve-online-google-facebook"
); ?></h4>
			<button type="button" class="notice-dismiss" id="mo_oauth_mo_server"><span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	</form>
	<script>
		jQuery("#mo_oauth_mo_server").click(function() {
			jQuery("#mo_oauth_mo_server_form").submit();
			//jQuery("#notice notice-info").hide();
		});
	</script>
	<?php } ?>
	<div class="mo_tutorial_overlay" id="mo_tutorial_overlay" hidden></div>
	<div id="tab">
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ($currenttab == "config" || $currenttab === "") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=config">OAuth Clients</a>
			<a class="nav-tab <?php if ($currenttab == "general_settings") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=general_settings">Configurations</a>
			<a class="nav-tab <?php if ($currenttab == "attributemapping") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=attributemapping">Server Response</a>
			<a class="nav-tab <?php if ($currenttab == "openid_support") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=openid_support">OpenID Support</a>
			<a class="nav-tab <?php if ($currenttab == "requestfordemo") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=requestfordemo">Trials Available</a>
			<a class="nav-tab <?php if ($currenttab == "login") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=login">Account Setup</a>
			<a class="nav-tab <?php if ($currenttab == "licensing") {
       echo "nav-tab-active";
   } ?>" href="admin.php?page=mo_oauth_server_settings&tab=licensing">Licensing Plans</a>
		</h2>
	</div>
	<div class="miniorange_container">
		<table style="width:100%;">
			<tr>
				<td style="vertical-align:top;width:65%;">

					<?php if ($currenttab == "licensing") {
         mo_oauth_server_app_licensing();
     } elseif ($currenttab == "openid_support") {
         mo_oauth_server_openid_support();
     } elseif ($currenttab == "general_settings") {
         mo_oauth_server_general_settings();
     } elseif ($currenttab == "attributemapping") {
         mo_oauth_server_attribute_mapping();
     } elseif ($currenttab == "requestfordemo") {
		 MOOAuth_Server_Admin_RFD::requestfordemo();
     } elseif ($currenttab == "config" || $currenttab === "") {
         mo_oauth_server_apps_config();
     } elseif (
         get_option("goto_registration") == true ||
         $currenttab == "login"
     ) {
         if (
             "MO_OAUTH_REGISTRATION_COMPLETE" ===
                 get_option("mo_oauth_server_registration_status") ||
             "MO_OAUTH_CUSTOMER_RETRIEVED" ===
                 get_option("mo_oauth_server_registration_status") ||
             boolval(mo_oauth_server_is_customer_registered())
         ) {
             mo_oauth_show_customer_details();
         } elseif (get_option("mo_oauth_server_verify_customer") == "true") {
             mo_oauth_server_show_verify_password_page();
         } elseif (
             trim(get_option("mo_oauth_admin_email")) != "" &&
             trim(get_option("mo_oauth_server_admin_api_key")) == "" &&
             get_option("mo_oauth_server_new_registration") != "true"
         ) {
             mo_oauth_server_show_new_registration_page();
         } else {
             delete_option("password_mismatch");
             mo_oauth_server_show_new_registration_page();
         }
     } elseif ($currenttab == "requestforquote") {
         mo_oauth_server_requestforquote();
     } else {
         mo_oauth_server_apps_config();
     } ?>
				</td>
				<?php if ($currenttab != "licensing" && $currenttab != "requestforquote") { ?>
				<td style="vertical-align:top;padding-left:1%;">
					<?php mo_oauth_server_miniorange_support(); ?>
					<br><br />
				</td>
				<?php } ?>
			</tr>
		</table>

	</div>
	<?php
}
function mo_oauth_show_customer_details()
{
    ?>
	<div class="mo_table_layout">
		<h2>Thank you for registering with miniOrange.</h2>

		<table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:85%">
			<tr>
				<td style="width:45%; padding: 10px;">miniOrange Account Email</td>
				<td style="width:55%; padding: 10px;"><?php echo esc_attr(
        get_option("mo_oauth_admin_email")
    ); ?></td>
			</tr>
			<tr>
				<td style="width:45%; padding: 10px;">Customer ID</td>
				<td style="width:55%; padding: 10px;"><?php echo esc_attr(
        get_option("mo_oauth_server_admin_customer_key")
    ); ?></td>
			</tr>
		</table>
		<br /><br />

		<table>
			<tr>
				<td>
					<form name="f1" method="post" action="" id="mo_oauth_goto_login_form">
						<input type="hidden" value="change_miniorange" name="option" />
						<input type="submit" value="Change Email Address" class="button button-primary button-large" />
					</form>
				</td>
				<td>
					<a href="<?php echo add_query_arg(
         ["tab" => "licensing"],
         esc_url_raw(htmlentities($_SERVER["REQUEST_URI"]))
     ); ?>"><input type="button" class="button button-primary button-large" value="Check Licensing Plans" /></a>
				</td>
			</tr>
		</table>

		<br />
	</div>

	<?php
}
function mo_oauth_server_show_new_registration_page()
{
    if (mo_oauth_server_is_customer_registered()) {
        mo_oauth_show_customer_details();
    } else {

        update_option("mo_oauth_server_new_registration", "true", false);
        $current_user = wp_get_current_user();
        ?>
	<!--Register with miniOrange-->
	<form name="f" method="post" action="">
		<input type="hidden" name="option" value="mo_oauth_register_customer" />
		<div class="mo_table_layout">
			<div id="toggle1" class="panel_toggle">
				<h3>Register with miniOrange</h3>
			</div>
			<div id="panel1">
				<!--<p><b>Register with miniOrange</b></p>-->
				<!-- <p>Please enter a valid Email ID that you have access to. You will be able to move forward after verifying an OTP that we will be sending to this email.
					</p> -->
				<p style="font-size:14px;"><b>Why should I register? </b></p>
				<div id="help_register_desc" style="background: aliceblue; padding: 10px 10px 10px 10px; border-radius: 10px;">
					You should register so that in case you need help, we can help you with step by step instructions.
					<b>You will also need a miniOrange account to upgrade to the premium version of the plugins.</b> We do not store any information except the email that you will use to register with us.
				</div>
				</p>
				<table class="mo_settings_table">
					<tr>
						<td><b>
								<font color="#FF0000">*</font>Email:
							</b></td>
						<td><input class="mo_table_textbox" type="email" name="email" required placeholder="person@example.com" value="<?php echo esc_attr(
          get_option("mo_oauth_admin_email")
      ); ?>" />
						</td>
					</tr>
					<tr class="hidden">
						<td><b>
								<font color="#FF0000">*</font>Website/Company Name:
							</b></td>
						<td><input class="mo_table_textbox" type="text" name="company" required placeholder="Enter website or company name" value="<?php echo esc_attr(
          $_SERVER["SERVER_NAME"]
      ); ?>" /></td>
					</tr>
					<tr class="hidden">
						<td><b>&nbsp;&nbsp;First Name:</b></td>
						<td><input class="mo_openid_table_textbox" type="text" name="fname" placeholder="Enter first name" value="<?php echo esc_attr(
          $current_user->user_firstname
      ); ?>" /></td>
					</tr>
					<tr class="hidden">
						<td><b>&nbsp;&nbsp;Last Name:</b></td>
						<td><input class="mo_openid_table_textbox" type="text" name="lname" placeholder="Enter last name" value="<?php echo esc_attr(
          $current_user->user_lastname
      ); ?>" /></td>
					</tr>
					<tr class="hidden">
						<td></td>
						<td>We will call only if you need support.</td>
					</tr>
					<tr>
						<td><b>
								<font color="#FF0000">*</font>Password:
							</b></td>
						<td><input class="mo_table_textbox" required type="password" name="password" placeholder="Choose your password (Min. length 8)" /></td>
					</tr>
					<tr>
						<td><b>
								<font color="#FF0000">*</font>Confirm Password:
							</b></td>
						<td><input class="mo_table_textbox" required type="password" name="confirmPassword" placeholder="Confirm your password" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><br><input type="submit" name="submit" value="Register" class="button button-primary button-large" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="button" name="mo_oauth_goto_login" id="mo_oauth_goto_login" value="Already have an account?" class="button button-primary button-large" />&nbsp;&nbsp;</td>
					</tr>
				</table>
			</div>
		</div>
	</form>
	<form name="f1" method="post" action="" id="mo_oauth_goto_login_form">
		<?php wp_nonce_field("mo_oauth_goto_login"); ?>
		<input type="hidden" name="option" value="mo_oauth_goto_login" />
	</form>
	<script>
		jQuery("#phone").intlTelInput();
		jQuery('#mo_oauth_goto_login').click(function() {
			jQuery('#mo_oauth_goto_login_form').submit();
		});
	</script>
	<?php
    }
}
function mo_oauth_server_show_verify_password_page()
{
    ?>
	<!--Verify password with miniOrange-->
	<form name="f" method="post" action="">
		<input type="hidden" name="option" value="mo_oauth_verify_customer" />
		<div class="mo_table_layout">
			<div id="toggle1" class="panel_toggle">
				<h3>Login with miniOrange</h3>
			</div>
			<div id="panel1">
				</p>
				<table class="mo_settings_table">
					<tr>
						<td><b>
								<font color="#FF0000">*</font>Email:
							</b></td>
						<td><input class="mo_table_textbox" type="email" name="email" required placeholder="person@example.com" value="<?php echo esc_attr(
          get_option("mo_oauth_admin_email")
      ); ?>" /></td>
					</tr>
					<td><b>
							<font color="#FF0000">*</font>Password:
						</b></td>
					<td><input class="mo_table_textbox" required type="password" name="password" placeholder="Choose your password" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" name="submit" value="Login" class="button button-primary button-large" />&nbsp;&nbsp;
	</form>

	<input type="button" name="back-button" id="mo_oauth_back_button" onclick="document.getElementById('mo_oauth_change_email_form').submit();" value="Back" class="button button-primary button-large" />

	<form id="mo_oauth_change_email_form" method="post" action="">
		<input type="hidden" name="option" value="mo_oauth_change_email" />
	</form>
	</td>
	</td>
	&nbsp;&nbsp;
	<a target="_blank" rel="noopener" href="<?php echo esc_attr(
     get_option("host_name")
 ) . "/moas/idp/userforgotpassword"; ?>">Forgot
		your password?</a></td>
	</tr>
	</table>
</div>
</div>
<?php
}

function mo_oauth_server_sign_in_settings()
{
    ?>

<div class="mo_table_layout">
	<h2>Sign in options</h2>

	<h4>Option 1: Use a Widget</h4>
	<ol>
		<li>Go to Appearances > Widgets.</li>
		<li>Select <b>"miniOrange OAuth"</b>. Drag and drop to your favourite location and save.</li>
	</ol>

	<h4>Option 2: Use a Shortcode</h4>
	<ul>
		<li>Place shortcode <b>[mo_oauth_login]</b> in wordpress pages or posts.</li>
	</ul>
</div>
<?php
}
function mo_oauth_server_app_howtosetup($client_object)
{
    if (!is_null($client_object)) { ?>
<style>
	.tableborder {
		border-collapse: collapse;
		width: 100%;
		border-color: #eee;
	}

	.tableborder th,
	.tableborder td {
		text-align: left;
		padding: 8px;
		border-color: #eee;
	}

	.tableborder tr:nth-child(even) {
		background-color: #f2f2f2
	}

	.custom_table {
		font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
		border-collapse: collapse;
		width: 100%;
	}

	.custom_table td,
	.custom_table th {
		border: 1px solid #ddd;
		padding: 8px;
	}

	.custom_table tr:nth-child(even) {
		background-color: #f2f2f2;
	}

	.custom_table tr:hover {
		background-color: #ddd;
	}

	.custom_table th {
		padding-top: 12px;
		padding-bottom: 12px;
		text-align: left;
		background-color: #4CAF50;
		color: white;
	}
</style>
<div id="how_to_setup" class="mo_table_layout">
	<h2>Endpoints</h2>
	<p>You can configure below endpoints in your OAuth client.<p>
	<hr>
	<table class="tableborder">
	<?php
    	$plugin_folder_name = basename(dirname(__FILE__));
    	$base_url_endpoints = site_url() . "/";
	    if (is_multisite()) {
	        $base_url_endpoints = network_site_url();
	    }
    ?>
    	<?php 
            $moOauthServerDb = new MoOauthServerDb();
            $clientlist = $moOauthServerDb->get_clients();
            $no_of_config_clients = count($clientlist);
            if ($no_of_config_clients == 1) {
        ?>
            <tr>
                <td><b>Issuer Endpoint </b> : </td>
                <td>
                    <p id='mo_oauth_server_issuer_endpoint' style='display:inline;'><?php
                        echo esc_attr($base_url_endpoints);
						echo esc_attr("wp-json/moserver/");
                        $client_id = $clientlist[0]->client_id;
                        echo esc_attr($client_id);
                    ?></p>
                </td>
                <td> <?php echo esc_attr(
                    mo_oauth_server_get_copy_icon("mo_oauth_server_issuer_endpoint")
                    ); ?>
                </td>
            </tr>
        <?php } ?>
		<tr>
			<td><b><?php echo esc_attr($client_object["authorize"]); ?> </b> : </td>
			<td>
				<p id='mo_oauth_server_authorize_endpoint' style='display:inline;'><?php 
					echo esc_attr($base_url_endpoints);?>wp-json/moserver/authorize</p>
			</td>
			<td> <?php echo esc_attr(
         		mo_oauth_server_get_copy_icon("mo_oauth_server_authorize_endpoint")
     			); ?>
     		</td>
		</tr>
		<tr>
			<td><b><?php echo esc_attr($client_object["token"]); ?> </b> : </td>
			<td>
				<p id='mo_oauth_server_token_endpoint' style='display:inline;'><?php echo esc_attr(
          			$base_url_endpoints
      				);?>wp-json/moserver/token</p>
			</td>
			<td><?php echo esc_attr(
		        	mo_oauth_server_get_copy_icon("mo_oauth_server_token_endpoint")
		    	); ?>
		    </td>
		</tr>
		<?php
    		if ("openidconnect" != $client_object["type"]) { ?>
		<tr>
			<td><b><?php echo esc_attr($client_object["userinfo"]); ?> </b> : </td>
			<td>
				<p id='mo_oauth_server_userinfo_endpoint' style='display:inline;'><?php 
					echo esc_attr($base_url_endpoints);?>wp-json/moserver/resource</p>
			</td>
			<td><?php echo esc_attr(
			        mo_oauth_server_get_copy_icon("mo_oauth_server_userinfo_endpoint")
			    ); ?>
			</td>
		</tr>

		<?php }
		    if (isset($client_object["custom fields"])) {
		        $custom_fields = $client_object["custom fields"];
		        foreach ($custom_fields as $key => $value) {
		            echo "<tr><td><b>" .
		                esc_attr($key) .
		                " </b> : </td><td>" .
		                esc_attr($value) .
		                "</td><td></td></tr>";
		        }
		    }
		?>
                <?php 
                    $moOauthServerDb = new MoOauthServerDb();
                    if ($no_of_config_clients == 1) {
                ?>
                    <tr>
                        <td><b>OpenID Connect Discovery </b> : </td>
                        <td>
                            <p id='mo_oauth_server_discovery_document_endpoint' style='display:inline;'><?php echo esc_attr($base_url_endpoints)."wp-json/moserver/". esc_attr($client_id) . "/.well-known/openid-configuration";?></p>
                        </td>
                        <td><?php echo esc_attr(
						        mo_oauth_server_get_copy_icon("mo_oauth_server_discovery_document_endpoint")
						    ); ?>
						</td>
                    </tr>
					<tr>
						<td><b>JWKS Endpoint </b> : </td>
						<td>
								<p id='mo_oauth_server_jwks_endpoint' style='display:inline;'><?php echo esc_attr($base_url_endpoints)."wp-json/moserver/". esc_attr($client_id) . "/.well-known/keys";?></p>
						</td>
						<td><?php echo esc_attr(
							        mo_oauth_server_get_copy_icon("mo_oauth_server_jwks_endpoint")
							    ); ?>
							</td>
					</tr>
				<?php } ?>
				<tr>
					<td><b>Introspection Endpoint </b> : </td>
					<td>
						<font color="red"> [PREMIUM]</font>
					</td>
					<td></td>
				</tr>
				<tr>
					<td><b>OpenID Single Logout Endpoint </b> : </td>
					<td>
						<font color="red"> [PREMIUM]</font>
					</td>
					<td></td>
				</tr>				
				<tr>
					<td><b>Supported scopes</b> : </td>
					<td>profile openid email</td>
					<td></td>
				</tr>
			</table>
			<p><b>NOTE</b>: If you are getting 404 for the above endpoints, please make sure you haven't selected <b>Plain</b> in the Permalink Settings</p>
			<hr><br>
			<h2>Scope Based Response</h2>
			<p>You can customize your response using <strong>scope based response</strong> feature.</strong>
				<font color="red"> [PREMIUM]</font>
			</p>
			<table class="custom_table">
				<tr>
					<th>Scope</th>
					<th>Attributes</th>
				</tr>
				<tr>
					<td>email</td>
					<td>email</td>
				</tr>
				<tr>
					<td>profile</td>
					<td>firstname, lastname, username, nickname, displayname</td>
				</tr>
				<tr>
					<td>custom</td>
					<td>All configured custom attributes.</td>
				</tr>
				<tr>
					<td>openid</td>
					<td>id, token_type, issuer, audience, jwt_token_id, issued_at, expiry_time</td>
				</tr>
				<tr>
					<td colspan=2 style="font-style: italic;font-size: 12px;">Note: You can use multiple scopes separated by spaces to get different attributes in the response.<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;e.g. email profile, custom profile, email custom</font>
					</td>
				</tr>
			</table>

			<hr><br>
			<h2>Grant Types</h2>

			<table style="width:100%">
				<tr>
					<td>You can configure various authentication and authorization flows based on the following Grant Types.</td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/oauth-api-document"><b>Click here</b></a> to know how this is useful]</td>
				</tr>
			</table>
			<br>

			<table class="custom_table">
				<thead>
					<tr>
						<th>Grant Types</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr>

						<td>Authorization Code Grant</td>
						<td>The Authorization Code grant type is used by web and mobile apps.
							It requires the client to exchange authorization code with access token from the server.</td>
					</tr>
					<tr>
						<td>Password Grant<font color="red"> [PREMIUM]</font>
						</td>
						<td>Password grant is used by application to exchange user's credentials for access token.
							This, generally, should be used by internal applications.</td>
					</tr>
					<tr>
						<td>Client Credentials<font color="red"> [PREMIUM]</font>
						</td>
						<td>Client Credentials grant can be used for machine to machine authentication. In this grant a specific user is not authorized but rather the credentials are verified and a generic access_token is returned.</td>
					</tr>
					<tr>
						<td>Implicit Grant<font color="red"> [PREMIUM]</font>
						</td>
						<td>The Implicit grant type is a simplified version of the Authorization Code Grant flow.
							OAuth providers directly offer access token when using this grant type.</td>
					</tr>
					<tr>
						<td>Refresh Token Grant<font color="red"> [PREMIUM]</font>
						</td>
						<td>The Refresh Token grant type is used by clients.
							This can help in keeping user session persistent.</td>
					</tr>
					<tr>
						<td>Authorization Code Grant with PKCE flow<font color="red"> [PREMIUM]</font>
						</td>
						<td>PKCE is an extension to the Authorization Code flow to prevent CSRF and authorization code injection attacks.</td>
					</tr>
				</tbody>
			</table>
</div>

<?php }
}
function mo_oauth_server_app_licensing()
{
    $registered = mo_oauth_server_is_customer_registered();
    $login_url = get_option("host_name") . "/moas/login";
    $username = get_option("mo_oauth_admin_email");
    $payment_url = get_option("host_name") . "/moas/initializepayment";
    echo '<div class="mo_idp_divided_layout mo-idp-full">';
    if (!$registered) {
        echo '<div style="display:block;margin-top:10px;color:red;width: 99%;
                            background-color:rgba(251, 232, 0, 0.15);
                            padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">
		        You have to <a href="admin.php?page=mo_oauth_server_settings&tab=login">
		        Register or Login with miniOrange</a> in order to be able to Upgrade.
		      </div>';
    }
    echo '   <form style="display:none;" id="mo_idp_request_quote_form" action="admin.php?page=mo_oauth_server_settings&tab=requestforquote" method="post">
                <input type="text" name="plan_name" id="plan-name" value="" />
                <input type="text" name="plan_users" id="plan-users" value="" />
            </form>
            
            <form style="display:none;" id="mocf_loginform" action="' .
        esc_url_raw($login_url) .
        '" target="_blank" method="post">
				<input type="email" name="username" value="' .
        esc_attr($username) .
        '" />
				<input type="text" name="redirectUrl" value="' .
        esc_url_raw($payment_url) .
        '" />
				<input type="text" name="requestOrigin" id="requestOrigin"  />
			</form>
            
            <div class="mo_idp_pricing_layout mo-idp-center">
                <h2>LICENSING PLANS<span style="float:right; font-size:13px">Need guidance with pricing? Please drop us an email at <a href="mailto:oauthsupport@xecurify.com">oauthsupport@xecurify.com</a></span>
                </h2>
                <hr>  
                <br>
                    <table class="mo_idp_license_plan mo_idp_license_table">
                        <tr>
                            <td class="license_plan_points" style="border-radius:12px 12px 0 0; width: 13%;"><b>Licensing Plan Name</b></td>
                            <!-- <td colspan=2 class="license_plan_title" style="width: 25%;"><span class="license_plan_name">LITE PLAN</span><br><p style="font-size:20px;">(Users hosted in miniOrange Cloud)</p></td> -->
                            <td class="license_plan_title" style="width: 25%;"><span class="license_plan_name">PREMIUM PLAN</span><br><p style="font-size:20px;">(Users stored in your own WordPress Database)</p></td>
                            <td class="license_plan_title"><span class="license_plan_name">ALL-INCLUSIVE PLAN</span><br><p style="font-size:20px;">(Users hosted in miniOrange or Enterprise Directory like Azure AD, Active Directory, LDAP, Office365, Google Apps or any 3rd party providers using SAML, OAuth, Database, APIs etc)</p></td>
                        </tr>
                        <tr style="background-color:#95d5ba;">
                            <td class="license_plan_points" rowspan=2><b>User Slabs / Pricing</b></td>
                            <td style="padding: 20px; line-height: 1.8;">
                                <b>Yearly Pricing <span class="dashicons dashicons-info mo-info-icon"><span class="mo-info-text">Number of users indicate any user that authenticated during a given <b><u>year</u></b></span></span>
                                <br><span style="color: red;">(50% from 2nd year onwards)</span></b>
                            </td>
                            <td><b>Monthly / Yearly Pricing</b></td>
                        </tr>
                        <tr>
                            <td class="mo_license_upgrade_button"><a onclick="mo2f_upgradeform(\'wp_oauth_server_enterprise_plan\')" style="display: block; width: 100%; text-decoration: none; color:white;"><b style="font-weight:700; letter-spacing:2px;">UPGRADE NOW</b></a></td>
                            <td class="mo_license_upgrade_button"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="display: block; width: 100%; text-decoration: none; color:white;"><b style="font-weight:700; letter-spacing:2px;">REQUEST A QUOTE</b></a></td>
                        </tr>
                        <tr>
                            <td class="license_plan_points">
                                <select id="mo_idp_users_dd" style="text-align: center; font-size:20px; color: #0071a1; border-color: #0071a1;">
                                    <option value="100" selected>1 - 100</option>
                                    <option value="200">101 - 200</option>
                                    <option value="300">201 - 300</option>
                                    <option value="400">301 - 400</option>
                                    <option value="500">401 - 500</option>
                                    <option value="1000">501 - 1000</option>
                                    <option value="2000">1001 - 2000</option>
                                    <option value="3000">2001 - 3000</option>
                                    <option value="4000">3001 - 4000</option>
                                    <option value="5000">4001 - 5000</option>
                                    <option value="5000+">5000+</option>
                                    <option value="UL">Unlimited</option>
                            </td>
                            <td class="mo_idp_price_row mo_idp_price_slab_100" style="display: table-cell;"><b>$</b>450<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$225</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_100" style="display: table-cell;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                            <td class="mo_idp_price_row mo_idp_price_slab_200" style="display: none;"><b>$</b>550<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$275</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_200" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                            <td class="mo_idp_price_row mo_idp_price_slab_300" style="display: none;"><b>$</b>650<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$325</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_300" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                            <td class="mo_idp_price_row mo_idp_price_slab_400" style="display: none;"><b>$</b>750<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$375</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_400" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_500" style="display: none;"><b>$</b>850<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$425</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_500" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_1000" style="display: none;"><b>$</b>1250<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$625</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_1000" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_2000" style="display: none;"><b>$</b>1600<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$800</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_2000" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_3000" style="display: none;"><b>$</b>1900<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$950</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_3000" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_4000" style="display: none;"><b>$</b>2150<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$1075</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_4000" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_5000" style="display: none;"><b>$</b>2400<span style="font-size: 15px;"> for the 1<sup>st</sup> year<br><b style="font-size: 18px;">$1200</b> from 2<sup>nd</sup> year onwards</span></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_5000" style="display: none;"><span style="">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_5000p" style="display: none;"><u style="color:orange; font-size: 21px;"><a id="wp_yearly_5K" onclick="gatherplaninfo(\'wp_yearly\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_5000p" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>

                            <td class="mo_idp_price_row mo_idp_price_slab_ul" style="display: none;"><u style="color:orange; font-size: 21px;"><a id="wp_yearly_UL" onclick="gatherplaninfo(\'wp_yearly\',\'UL\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                            <td class="mo_idp_all_inc_cell mo_idp_price_slab_ul" style="display: none;">Starts from <b>$</b>1/user/month<br><u style="color:orange; font-size: 21px;"><a onclick="gatherplaninfo(\'all_inclusive\',\'5K\')" style="color:orange;"><b>Request a Quote</b></a></u></td>
                        </tr>
 
                        <tr>
                            <td class="license_plan_points"><b>User Storage Location</b></td>
                            <td class="license_plan_miniorange">Keep Users in WordPress Database</td>
                            <td class="license_plan_miniorange">Keep Users in miniOrange Database or Enterprise Directory like Azure AD, Active Directory, LDAP, Office 365, Google Apps  or any 3rd party providers using SAML, OAuth, Database, APIs etc.</td>
                        </tr>
                        <tr>
                            <td class="license_plan_points"><b>Password Management</b></td>
                            <td class="license_plan_miniorange">Passwords will be stored in your WordPress Database</td>
                            <td class="license_plan_miniorange">Passwords can be managed by miniOrange or by the 3rd party Identity Provider</td>
                        </tr>
                        <tr>
                            <td class="license_plan_points"><b>SSO Support</b></td>
                            <td class="license_plan_miniorange">Single-Protocol SSO Support<br>&nbsp;<br>OAuth<br>OpenID Connect<br>JWT</td>
                            <td class="license_plan_miniorange">Cross-Protocol SSO Support<br>SAML<br>OAuth<br>OpenID Connect<br>JWT</td>
                        </tr>
                        <tr>
                            <td class="license_plan_points"><b>User Registration</b></td>
                            <td class="license_plan_miniorange">Use your own existing WordPress Sign-up form</td>
                            <td class="license_plan_miniorange">Sign-up via miniOrange Login Page</td>
                        </tr> 
                        <tr>
                            <td class="license_plan_points"><b>Login Page</b></td>
                            <td class="license_plan_miniorange">Use your own existing WordPress Login Page</td>
                            <td class="license_plan_miniorange">Fully customizable miniOrange Login Page</td>
                        </tr>
                        <tr>
                            <td class="license_plan_points"><b>Custom Domains</b></td>
                            <td class="license_plan_miniorange">Use your own WordPress domain</td>
                            <td class="license_plan_miniorange">Fully Custom Domain is provided</td>
                        </tr>
                        <tr>
                            <td class="license_plan_points"><b>Social Providers</b></td>
                            <td class="license_plan_miniorange"><a href="https://plugins.miniorange.com/social-login-social-sharing#pricing" target="_blank" style="color:orange;">Click here</a> to purchase Social Login Plugin seperately</td>
                            <td class="license_plan_miniorange">Included<br>(Facebook, Twitter, Google+, etc)</td>
                        </tr>                                            
                        <tr>
                            <td class="license_plan_points"><b>Multi-Factor Authentication</b></td>
                            <td class="license_plan_miniorange"><a href="https://plugins.miniorange.com/2-factor-authentication-for-wordpress#pricing" target="_blank" style="color:orange;">Click here</a> to purchase Multi-Factor Plugin seperately</td>
                            <td class="license_plan_miniorange">Included</td>
                        </tr>                          
                        <tr>
                            <td class="license_plan_points" style="border-radius:0 0 12px 12px;"><b>User Provisioning</b></td>
                            <td class="license_plan_wp_premium" style="border-radius:0 0 12px 12px;">Not Included</td>
                            <td class="license_plan_miniorange" style="border-radius:0 0 12px 12px;">Included</td>
                        </tr>
                    
                    </table>
<!--
                    <table class="mo_idp_pricing_table" style="margin:auto;">
                        <tr>
                            <td><h2>Choose your Plan : </h2></td>
                            <td>
                                <select style="width:85%">
                                    <option>WordPress Premium Plan</option>
                                    <option>miniOrange Lite Plan</option>
                                    <option>miniOrange All Inclusive Plan</option>
                                </select>
                            </td>
                            <td>
                                <select style="width:75%">
                                    <option>Pay Monthly</option>
                                    <option>Pay Yearly</option>
                                </select>
                            </td>
                            <td>
                                <a href="https://www.google.com" target="_blank">Proceed to Payment Page</a> <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </td>
                        </tr>
                    </table>
-->
            </div>
            <div id="disclamer" class="mo_idp_pricing_layout mo-idp-center">
            	<h3>* The Premium plugin is compatible with WordPress Multisite network.</h3>
                <h3>* Steps to Upgrade to Premium Plugin -</h3>
                <p>
                    1. You will be redirected to miniOrange Login Console. 
                    Enter your password with which you created an account with us. 
                    After that you will be redirected to payment page.
                </p>
                <p>
                    2. Enter you card details and complete the payment. 
                    On successful payment completion, you will see the link to download the premium plugin.
                </p>
                <p>
                    3. Once you download the premium plugin, just unzip it and replace the folder with existing plugin. <br>
                    <b>Note: Do not first delete and upload again from wordpress admin panel as your already saved settings will get lost.</b></p>
                    <p>4. From this point on, do not update the plugin from the Wordpress store.</p>
                    <h3>** End to End Integration - </h3>
                    <p> 
                        We will setup a Conference Call / Gotomeeting and do end to end configuration for you. 
                        We provide services to do the configuration on your behalf. 
                    </p>
                    If you have any doubts regarding the licensing plans, you can mail us at 
                    <a href="mailto:oauthsupport@xecurify.com"><i>oauthsupport@xecurify.com</i></a> 
                    or submit a query using the <b>support form</b>.
                </p>
            </div>
            <div class="mo_idp_pricing_layout mo-idp-center">
                <p>At miniOrange, we want to ensure you are 100% happy with your purchase. If the premium plugin you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved, please email us at <a href="mailto:info@xecurify.com" target="_blank">info@xecurify.com</a> for any queries regarding the return policy.</p>
            </div>
        </div>';
}
function mo_oauth_server_openid_support()
{
    $enable_oidc = (bool) get_option("mo_oauth_server_enable_oidc")
        ? get_option("mo_oauth_server_enable_oidc")
        : "on";
    echo '<div id="enable_oidc" class="mo_table_layout">';
    echo '
	<form name="f" method="post" action="" style="padding: 10px;">
		<div id="toggle3" class="panel_toggle">
			<table style="width:100%">
				<tr>
					<td><h3>OpenID Connect</h3></td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/openid-support"><b>Click here</b></a> to know how this is useful]</td></tr>
			</table>
		</div>
		<div id="mo_server_enable_oidc">
			Enable or Disable the support for OpenID Connect Protocol.<br><br>
			<label class="mo_switch">
                <input autocomplete="off" onclick="turnOff(this, \'mo_server_oidc_toggle\')"';
    if ($enable_oidc === "on") {
        echo "checked";
    }
    echo ' type="checkbox" name="mo_server_enable_oidc">
				<span id="mo_server_oidc_toggle" class="mo_slider mo_round with_on_text">';
    if ($enable_oidc === "on") {
        echo "ON";
    }
    echo '</span>
			</label>&emsp;<strong>Enable OpenID Connect Support</strong>

			<br><br>
        </div>
        <input type="hidden" name="option" value="mo_oauth_server_enable_oidc" />
		<input type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';
    echo '
	<script>
		function showToast(element, checked, id) {
			element.checked = checked;
			var x = document.getElementById(id);
			x.classList.add("show");
			setTimeout(function(){ x.classList.remove("show") }, 6000);
        }

        function turnOff(element, id) {
            var sp = document.getElementById(id);
            if(element.checked != true) {
                sp.innerHTML = "";
            } else if(element.checked == true) {
                sp.innerHTML = "ON";
            }
        }
	</script>
	';
}
function mo_oauth_server_general_settings()
{
    $master_switch = (bool) get_option("mo_oauth_server_master_switch")
        ? get_option("mo_oauth_server_master_switch")
        : "on";
    $enforce_state = (bool) get_option("mo_oauth_server_enforce_state")
        ? get_option("mo_oauth_server_enforce_state")
        : "on";
    $token_length = (bool) get_option("mo_oauth_server_token_length")
        ? (int) get_option("mo_oauth_server_token_length")
        : (int) 32;
    $expiry_time = (bool) get_option("mo_oauth_expiry_time")
        ? (int) get_option("mo_oauth_expiry_time")
        : 3600;
    $refresh_expiry_time = get_option("mo_oauth_refresh_expiry_time")
        ? get_option("mo_oauth_refresh_expiry_time")
        : 86400;
    $prompt_grant = (bool) get_option("mo_oauth_server_prompt_grant")
        ? get_option("mo_oauth_server_prompt_grant")
        : "on";
    echo '<div id="master_switch" class="mo_table_layout">';
    echo '
	<form name="f" method="post" action="" style="padding: 0px 5px;">
		<div id="toggle3" class="panel_toggle">
			<h3>Master Switch</h3>
		</div>
		<div id="mo_server_master_switch">
            Disabling master switch will stop sending/receiveing API calls from/to your OAuth Client application.
        <p>
			<label class="mo_switch">
                <input autocomplete="off" onclick="turnOff(this, \'mo_server_mswitch_indicator\')"';
    if ($master_switch === "on") {
        echo "checked";
    }
    echo ' type="checkbox" name="mo_server_master_switch">
				<span id="mo_server_mswitch_indicator" class="mo_slider mo_round with_on_text">';
    if ($master_switch === "on") {
        echo "ON";
    }
    echo '</span>
			</label>&emsp;<strong>Server</strong></p>
			<div id="mswitch_warning" style = "color: #FF0000;">';
    if (get_option("mo_oauth_server_master_switch") == "off") {
        echo "<p>Currently, your server is not responding to any API calls from your client applications.</p>";
    } else {
        echo "<br/>";
    }
    echo '</div>
        </div>
        <input type="hidden" name="option" value="mo_oauth_server_master_switch" />
		<input type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';
    echo '
<div id="generalsettings" class="mo_table_layout">
<form name="f" method="post" action="">
    <input type="hidden" name="option" value="mo_oauth_general_settings" />
    <div>
        <div id="toggle1" class="panel_toggle">
			<table style="width:100%">
				<tr>
					<td><h3>General Settings</h3></td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/token-settings"><b>Click here</b></a> to know how this is useful]</td></tr>
			</table>
        </div>
        <div id="panel1">
            <table class="mo_settings_table">
                <tr>
                    <td><b>Access Token Expiry Time :<br> ( In seconds )<span style="color:#FF0000">  [PREMIUM]</span></b></td>
                    <td><input class="mo_table_textbox" type="text" name="expiry_time"
                        required  value="' .
        esc_attr($expiry_time) .
        '" disabled/>
                    </td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td><b>Refresh Token Expiry Time :<br> ( In seconds )<span style="color:#FF0000">  [PREMIUM]</span></b></td>
                    <td><input class="mo_table_textbox" type="text" name="refresh_expiry_time"
                        required  value="' .
        esc_attr($refresh_expiry_time) .
        '" disabled/>
                    </td>
                </tr>
                <tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td><b>Token Length :</b></td>
                    <td><input class="mo_table_textbox" type="number" min="32" max="127" name="mo_server_token_length"
                        required value="';
    echo esc_attr($token_length);
    echo '" />
                    </td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" name="submit" value="Save Settings"
                        class="button button-primary button-large" /></td>
                </tr>
            </table>
        </div>
    </div>
</form>
</div>';
    echo '<div id="enforce_state" class="mo_table_layout">';
    echo '
	<form name="f" method="post" action="" style="padding: 0px 5px;">
		<div id="toggle3" class="panel_toggle">
			<table style="width:100%">
				<tr>
					<td><h3>State Parameter</h3></td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/enforce-state-parameters"><b>Click here</b></a> to know how this is useful]</td></tr>
			</table>
		</div>
		<div id="mo_server_enforce_state">
			
			<label class="mo_switch">
                <input checked onclick="showToast(this, true, \'mo_prompt_premium_text_state\')"  type="checkbox">
				<span id="mo_server_enforcestate_indicator" class="mo_slider mo_round with_on_text">ON</span>
			</label>&emsp;<strong>Enforce State Parameter</strong>
			<div id="mo_prompt_premium_text_state" class="mo_premium_text">This is a premium feature. Check our licensing page for more info.</div>

			When enabled, the authorization request will fail if state parameter is not provided or is incorrect.<br><br>
        </div>
        <input type="hidden" name="option" value="mo_oauth_server_enforce_state" />
		<input disabled type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';
    echo '<div id="prompt_grant" class="mo_table_layout">';
    echo '
	<form name="f" method="post" action="" style="margin: 0px 5px;">
		<div id="toggle2" class="panel_toggle">
			<table style="width:100%">
				<tr>
					<td><h3>Authorize/Consent Prompt</h3></td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/consent-screen"><b>Click here</b></a> to know how this is useful]</td></tr>
			</table>
		</div>
		<div id="prompt_grant_validation">
			<label class="mo_switch">
				<input checked onclick="showToast(this, true, \'mo_prompt_premium_text\')"  type="checkbox">
				<span class="mo_slider mo_round with_on_text">ON</span>
			</label>&emsp;<strong>Enable Authorize/Consent Prompt</strong><div id="mo_prompt_premium_text" class="mo_premium_text">This is a premium feature. Check our licensing page for more info.</div>

			If enabled, the server will show a consent screen where the user can allow/deny the applications.

			<br><br>
			<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/consent-screen"><b>Click here</b></a> to learn how to use this feature.

		</div>
		<br><br>
		<input disabled type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';
    echo '<div id="redirect_validation" class="mo_table_layout">';
    echo '
	<form name="f" method="post" action="" style="margin: 0px 5px;">
		<div id="toggle2" class="panel_toggle">
			<table style="width:100%">
				<tr>
					<td><h3>Redirect/Callback URI Validation</h3></td>
					<td align="right" style="margin-left:0">[<a target="_blank" href="https://developers.miniorange.com/docs/oauth/wordpress/server/redirect-uri-validation"><b>Click here</b></a> to know how this is useful]</td></tr>
			</table>
		</div>
		<div id="callback_validation">
			<strong>Note :</strong> Use in case of Dynamic or Conditional Callback/Redirect URIs.<br><br>
			<label class="mo_switch">
				<input disabled type="checkbox">
				<span onclick="showToast(this, true, \'mo_premium_text\')" class="mo_slider mo_round with_on_text"></span>
			</label>&emsp;<strong>Validate Redirect/Callback URIs</strong><div id="mo_premium_text" class="mo_premium_text">This is a premium feature. Check our licensing page for more info.</div>

			<strong>How to use this feature?</strong><br><br>
			By default, server is configured with default redirect URL. <br><br>
			Disable this feature, in case if your client wants to redirect to a different page for certain conditions, such as, pre-registered users and guest users.

		</div>
		<br><br>
		<input disabled type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';
    echo '
	<script>
		function showToast(element, checked, id) {
			element.checked = checked;
			var x = document.getElementById(id);
			x.classList.add("show");
			setTimeout(function(){ x.classList.remove("show") }, 6000);
        }

        function turnOff(element, id) {
            var sp = document.getElementById(id);
            if(element.checked != true) {
                if(id === "mo_server_mswitch_indicator") {
                    setTimeout(function(){ document.getElementById("mswitch_warning").classList.add("show") }, 200);
                }
                sp.innerHTML = "";
            } else if(element.checked == true) {
                if(id === "mo_server_mswitch_indicator") {
                    setTimeout(function(){ document.getElementById("mswitch_warning").classList.remove("show") }, 700);
                }
                sp.innerHTML = "ON";
            }
        }

	</script>
	';
}
function mo_oauth_server_attribute_mapping()
{
    echo '
	<style>
		.tableborder {border-collapse: collapse;width: 100%;border-color:#eee;}
		.tableborder th, .tableborder td {text-align: left;padding: 8px;border-color:#eee;}
		.tableborder tr:nth-child(even){background-color: #f2f2f2}
	</style>
	';
    $server_response = [];
    $attrs = [
        "username" => "user_login",
        "email" => "email",
        "first_name" => "first_name",
        "last_name" => "last_name",
        "display_name" => "display_name",
        "nickname" => "nickname",
    ];
    $user_info = get_userdata(wp_get_current_user()->ID);
    $attr_value = [];
    foreach ($user_info->data as $key => $value) {
        if (
            $key !== "user_pass" &&
            $key !== "user_activation_key" &&
            $key !== "user_status"
        ) {
            array_push($attr_value, $key);
        }
    }
    array_push($attr_value, "user_firstname");
    echo '
		<div id="basicattributemapping" class="mo_table_layout">
			<h3>Basic Attribute Mapping &emsp;<small class="mo_oauth_server_premium_feature"> [PREMIUM FEATURE]</small></h3>
			You can customize and send below attriutes in response to your OAuth Client\'s Get User Information request.<br><br>
			<table class="mo_settings_table tableborder">
				<tr><td><b>Attribute Name</b></td><td><b>Attribute Value</b></td></tr>';
    foreach ($attrs as $attr_name => $attr_value) {
        echo '<tr><td><input disabled type="text" placeholder="' .
            esc_attr($attr_name) .
            '" /></td>
					<td><select style="width: 150px;" disabled><option selected value="' .
            esc_attr($attr_name) .
            '">' .
            esc_attr($attr_value) .
            "</option></td></tr>";
    }
    echo '</table>
			<br><br><input disabled type="submit" name="submit" value="Save settings"
			class="button button-primary button-large" />
		</div>
	';
    echo '
	<div id="attributemapping" class="mo_table_layout">
	<form name="form-common" method="post" action="admin.php?page=mo_oauth_server_settings&tab=settings">
	<table class="mo_settings_table">';
    echo '
		  <tr><td colspan="2">
		<h3>Map Custom Attributes &emsp;<small class="mo_oauth_server_premium_feature"> [PREMIUM FEATURE]</small></h3>Map extra User attributes which you wish to be included in the OAuth response. <br/>
		<b>Note : </b>Enter the name you want to send as attribute name under Attribute Name text field and meta field name under the Attribute Value text field. <br>

		</td><td><input disabled type="button" value="+" class="button button-primary"  /></td>
						<td><input disabled type="button" value="-" class="button button-primary" /></td></tr><br/>
						<tr><td>
		<b><u>Example</u> : </b></td></tr><tr>
		<tr><td><b>Attribute Name</b></td><td><b>Attribute Value</b></td></tr>
		<td><input disabled value="Given Name" /></td>
		<td><input disabled value="first_name" /></td></tr>
						<tr><td>&nbsp;</td></tr>';
    echo '<tr id="save_config_element">
			<td><input disabled type="submit" name="submit" value="Save settings"
			class="button button-primary button-large" /></td>
			<td>&nbsp;</td>
		</tr>
		</table>
	</form>

		</div>';
}
function mo_oauth_server_apps_config()
{
    ?>
<style>
	.tableborder {
		border-collapse: collapse;
		width: 100%;
		border-color: #eee;
	}

	.tableborder th,
	.tableborder td {
		text-align: left;
		padding: 8px;
		border-color: #eee;
	}

	.tableborder tr:nth-child(even) {
		background-color: #f2f2f2
	}
</style>
<div class="mo_table_layout">
	<?php

		if(isset($_GET['action']) && 'delete' == sanitize_text_field($_GET['action'])){
			if(isset($_GET['client']))
				mo_oauth_server_delete_app(sanitize_text_field($_GET['client']), sanitize_text_field($_GET['clientid']));
		}

		if(isset($_GET['action']) && 'add' == sanitize_text_field($_GET['action'])){
			if(isset($_GET['clientId'])){
				$default_client = sanitize_text_field($_GET['clientId']);
				$client_object = mo_oauth_server_get_client_object($default_client);
				mo_oauth_server_add_app($client_object);
		    	mo_oauth_server_app_howtosetup($client_object);
			}else{
				mo_oauth_server_show_default_clients();
			}
		}
		else if(isset($_GET['action']) && 'update' == sanitize_text_field($_GET['action'])){
			$client_object = get_option('mo_oauth_server_client');
			$client_object = $client_object?$client_object:'oauth2';
			$client_object = mo_oauth_server_get_client_object($client_object);
			if(isset($_GET['client']))
				mo_oauth_server_update_app(sanitize_text_field($_GET['client']),$client_object);
        		mo_oauth_server_app_howtosetup($client_object);
		}
		else {
			$client_object = get_option('mo_oauth_server_client');
			$client_object = $client_object?$client_object:'oauth2';
			$client_object = mo_oauth_server_get_client_object($client_object);
			mo_oauth_client_list($client_object);
			mo_oauth_server_app_howtosetup($client_object);

		}  ?>
        
        <script type="text/javascript">
        	function moOauthServerCopyUrl(argument) {
				var temp = jQuery("<input>");
				jQuery("body").append(temp);
				if(jQuery("#" + argument).prop("tagName") == 'P'){
					temp.val(jQuery("#"+argument).text()).select();
				}
				else{
					temp.val(jQuery("#"+argument).val()).select();
				}
				document.execCommand("copy");
				temp.remove();
					var tooltip = document.getElementById(argument+"moTooltip");
					tooltip.innerHTML = "Copied";
        	}
        	function moOauthServerOutFunc(argument) {
			  	var tooltip = document.getElementById(argument+"moTooltip");
				tooltip.innerHTML = "Copy To Clipboard";
        	}
			function moOauthServerShowClientSecret(argument){			//Toggle Client Secret when eye icon is clicked
				var field = document.getElementById(argument);
				var showButton = document.getElementById("show-button");
				if(field.type == "password"){
					field.type = "text";
					showButton.className = "fa fa-eye-slash";
				}
				else{
						field.type = "password";
						showButton.className = "fa fa-eye";
					}
			}
        </script>
        <?php
}

function mo_oauth_server_get_client_object($clientId){
	if(!$clientId)
		return false;
	$defaultapps     = file_get_contents( MOSERVER_DIR . 'admin'.DIRECTORY_SEPARATOR.'partials'.DIRECTORY_SEPARATOR .'clients'.DIRECTORY_SEPARATOR .'partials'.DIRECTORY_SEPARATOR .'defaultclients.json', true);
	$defaultappsjson = json_decode( $defaultapps );
	foreach ( $defaultappsjson as $app_id => $application ) {
		if($app_id == $clientId){
			$application = (array) $application;
			return $application;
		}
	}
}
function mo_oauth_client_list($client_object)
{
    $moOauthServerDb = new MoOauthServerDb();
    $clientlist = $moOauthServerDb->get_clients();
    $disabled = count($clientlist) > 0 ? "disabled" : "";
    if (!empty($disabled)) {
        echo '<br> <span style="float:right">';
        mo_oauth_server_get_api_doc_link($client_object);
        echo "<a href='admin.php?page=mo_oauth_server_settings&tab=config&action=add'><button class=\"button button-primary button-large\" style='float:right' id=\"add_client\" " .
            esc_attr($disabled) .
            ">Add Client</button></a></span>";
        echo "<h3>Clients List</h3>";
        if ($disabled === "disabled") {
            echo "<p style='color:#a94442;background-color:#f2dede;border-color:#ebccd1;border-radius:5px;padding:12px'>" .
                __(
                    "You can only add 1 client with free version. Upgrade to",
                    "miniorange-oauth-20-server"
                ) .
                " <a href='admin.php?page=mo_oauth_server_settings&tab=licensing'><b>Premium</b></a> " .
                __("to add more.", "miniorange-oauth-20-server") .
                "</p>";
        }
        echo "<table class='tableborder'>";
        echo "<tr><th>Client Name</th><th>Client ID</th><th>Client Secret</th></tr>";
        $i = 0;
		$base_url_endpoints = site_url() . "/";
	    if (is_multisite()) {
	        $base_url_endpoints = network_site_url();
	    }
        foreach ($clientlist as $client) {
            echo "<tr><td>" .
                esc_attr($client->client_name) .
                "
		<br>
		<a href='admin.php?page=mo_oauth_server_settings&tab=config&action=update&client=".esc_attr($client->client_name)."'>Update</a> | 
		<a href='admin.php?page=mo_oauth_server_settings&tab=config&action=delete&client=".esc_attr($client->client_name)."&clientid=".esc_attr($client->client_id)."'\" onclick =\"return confirm('Are you sure you want to delete this item?');\">Delete</a> | 
		<a href=" . esc_attr($base_url_endpoints)."wp-json/moserver/". esc_attr($client->client_id) . "/.well-known/openid-configuration " . "target='_blank'" . ">Discovery Document</a> | 
		<a href='admin.php?page=mo_oauth_server_settings&tab=config&action=update&client=".esc_attr($client->client_name)."#enable_jwt_support'>JWT Settings</a>
		</td><td><input class='disabled-input' type='text' id='mo_oauth_server_client_id".esc_attr($i)."' style='border:none; background-color:transparent; box-shadow:none;' value='".esc_attr($client->client_id)."' disabled>";
		mo_oauth_server_get_copy_icon('mo_oauth_server_client_id'.$i);
		echo "</td><td>";

		$client_secret = mo_oauth_server_decrypt( esc_attr($client->client_secret), esc_attr($client->client_name) );
		
		echo "<input class='disabled-input password-with-toggle' type='password' id='mo_oauth_server_client_secret".esc_attr($i)."' style='border:none; background-color:transparent; box-shadow:none;' value='".esc_attr($client_secret)."' disabled>";
		
		mo_oauth_show_icon('mo_oauth_server_client_secret'.$i);
		mo_oauth_server_get_copy_icon('mo_oauth_server_client_secret'.$i);
		echo "</td></tr>";
		$i++;
    }
	echo "</table>";
        if(sizeof($clientlist) === 0) {
            ?>
                <p class="mo_oauth_server_noapps_text">There are no client apps configured.</p>
            <?php
        }
	echo "<br>";
	}else{
		mo_oauth_server_show_default_clients($client_object);		
	}
	if(sizeof($clientlist) > 0)
		echo "<p style='color:#a94442;background-color:rgba(255,108,55,0.4);border-color:#ebccd1;border-radius:5px;padding:12px'>".__('You can test the OAuth configuration using the sample','miniorange-oauth-20-server')." <a id='mo_oauth_server_sample_json' href='?option=downloadsamplejson&client=".esc_attr($client->client_id)."'><b>Postman Collection</b></a> ".__('file.','miniorange-oauth-20-server')."</p>";
	
	echo "</div>";
}

function mo_oauth_show_icon($id){
	?>
	<div class="moservertooltip"><span toggle="#password-field" class="moservertooltiptext" id="toggle_password">Show Client Secret</span><i class="fa fa-fw fa-eye" id="show-button" style="font-size:20px; vertical-align: middle;" aria-hidden="true" onclick="moOauthServerShowClientSecret('<?php echo esc_attr($id); ?>')"></i></div>
	<?php
}
function mo_oauth_server_get_copy_icon($id){
	?>
	<div class="moservertooltip"><span class="moservertooltiptext" id="<?php echo esc_attr($id) ?>moTooltip">Copy to clipboard</span><i class="fa fa-clipboard fa-border" style="font-size:20px; vertical-align: middle;" aria-hidden="true" onclick="moOauthServerCopyUrl('<?php echo esc_attr($id); ?>')" onmouseout="moOauthServerOutFunc('<?php echo esc_attr($id); ?>')"></i></div>
	<?php
}
function mo_oauth_server_add_app($client_object)
{
    $moOauthServerDb = new MoOauthServerDb();
    $clientlist = $moOauthServerDb->get_clients();
    ?>

	<div id="toggle2" class="panel_toggle">
		<h3>Add Client <span style="float:right;"><?php mo_oauth_server_get_api_doc_link(
      $client_object
  ); ?></span></h3>
	</div>
	<form id="form-common" name="form-common" method="post" action="admin.php?page=mo_oauth_server_settings&tab=config">
		<input type="hidden" name="option" value="mo_oauth_add_client" />
		<input type="hidden" name="client" value=<?php echo esc_attr(
      $_GET["clientId"]
  ); ?> />
		<table class="mo_settings_table">
			<tr id="client_name">
				<td><strong>
						<font color="#FF0000">*</font>Client Name :
					</strong></td>
				<td><input class="mo_table_textbox" required="" type="text" id="mo_oauth_custom_client_name" name="mo_oauth_custom_client_name" value=""></td>
			</tr>
			<tr id="redirect_uri">
				<td><strong>
						<font color="#FF0000">*</font>Redirect URI :
					</strong></td>
				<td><input class="mo_table_textbox" required="" pattern="https?://.+" type="url" name="mo_oauth_client_redirect_url" value=""></td>
			</tr>
			<tr>
				<td><input id="client_save" type="submit" name="submit" value="Save Client" class="button button-primary button-large" /></td>
			</tr>
		</table>
	</form>
</div>
<?php
}
function mo_oauth_server_gen_dropdown($selected, $array) {
	$html = "";
	foreach($array as $element) {
		$html .= '<option ';
		if($element === $selected) {
			$html .= 'selected';
		}
		$html .= ' value="'.$element.'">'.$element.'</option>';
	}
	$html .= "";
	return $html;
}
function mo_oauth_server_update_app($client_name, $client_object)
{
    $moOauthServerDb = new MoOauthServerDb();
    $clientlist = $moOauthServerDb->get_clients();
    $currentclient = false;
    // foreach ($clientlist as $client) {
    //     if ($client_name == $client->client_name) {
    //         $currentclient = $client;
    //         break;
    //     }
    // }
	$currentclient = $clientlist[0];
    if (!$currentclient) {
        return;
    }
    ?>

<div id="toggle2" class="panel_toggle">
	<h3>Update Application <span style="float:right;"><?php mo_oauth_server_get_api_doc_link(
     $client_object
 ); ?></span></h3>
</div>
<form id="form-common" name="form-common" method="post" action="admin.php?page=mo_oauth_server_settings">
	<input type="hidden" name="option" value="mo_oauth_update_client" />
	<table class="mo_settings_table">
		<tr>
			<td><strong>Client Name :</strong></td>
			<td><?php echo esc_attr(
       $currentclient->client_name
   ); ?><input class="mo_table_textbox" type="hidden" id="mo_oauth_custom_client_name" name="mo_oauth_custom_client_name" value="<?php echo esc_attr($currentclient->client_name); ?>"></td>
		</tr>
		<tr>
			<td><strong>
					<font color="#FF0000">*</font>Redirect URI :
				</strong></td>
			<td><input class="mo_table_textbox" required="" pattern="https?://.+" type="url" name="mo_oauth_client_redirect_url" value="<?php echo esc_attr(
       $currentclient->redirect_uri
   ); ?>"></td>
		</tr>
		<tr>
			<td><br><input type="submit" name="submit" value="Update Client" class="button button-primary button-large" /></td>
		</tr>
	</table>
</form>&nbsp;
</div>
<?php
		$sub = str_replace(" ", "_", $currentclient->client_name);
		$enable_jwt_support = (bool) get_option("mo_oauth_server_enable_jwt_support_for_$sub") ? get_option("mo_oauth_server_enable_jwt_support_for_$sub") : 'on';
		$jwt_signing_algo = (bool) get_option('mo_oauth_server_jwt_signing_algo_for_'.$sub) ? get_option('mo_oauth_server_jwt_signing_algo_for_'.$sub) : 'HS256';
		$all_algos = array(
			'HS256',
			'RS256'
		);
		$enable_private_key_jwt = (bool) get_option("mo_oauth_server_enable_private_key_jwt_client_auth_for_".$currentclient->client_id) ? get_option("mo_oauth_server_enable_private_key_jwt_client_auth_for_".$currentclient->client_id) : 'off';
		$x509_cert = (bool)get_option("mo_server_x509_cert_private_key_auth_for_".$currentclient->client_id) ? get_option("mo_server_x509_cert_private_key_auth_for_".$currentclient->client_id) : '';
?>
<?php
echo '<div id="enable_jwt_support" class="mo_table_layout">
	<form name="f" method="post" action="" style="padding: 10px;">
		<div id="toggle3" class="panel_toggle">
			<h3>JWT Support</h3>
		</div>
		<div id="mo_server_enable_jwt_support">
			Enable or Disable the support for JSON Web Tokens (JWT).<br><br>
			<label class="mo_switch">
                <input autocomplete="off" onclick="turnOff(this, \'mo_server_jwt_toggle\')"';
                if($enable_jwt_support === 'on') { echo "checked"; }
                echo ' type="checkbox" id="mo_server_enable_jwt_support_toggle" name="mo_server_enable_jwt_support_for_'.$currentclient->client_name.'">
				<span id="mo_server_jwt_toggle" class="mo_slider mo_round with_on_text">'; if($enable_jwt_support === 'on') { echo "ON"; } echo'</span>
			</label>&emsp;<strong>JWT Support</strong><br/><br/>
			<b>Note : </b>Enable only if JWT is supported by your OAuth/OpenID client.
		</div>
		<div id="mo_server_jwt_signing_algos" '; if($enable_jwt_support === 'off') { echo "hidden"; } echo '>
			<br/><br><hr>
			<h4>Signing Algorithms</h4>
			<table>
				<tr>
					<td><b>Algorithm:</b></td>
					<td><select name="mo_oauth_server_jwt_signing_algo_for_'.$currentclient->client_name.'" style="width: 150px;">';
					echo mo_oauth_server_gen_dropdown($jwt_signing_algo, $all_algos);
					echo '</select></td>
				</tr>
			</table>
			<div id="x509_cert" ';
				if(strpos($jwt_signing_algo, "HS") !== false) { echo "hidden"; }
			echo '>
				<br><hr>
				<h4>Signing Certificate:</h4>
				<p><span style="color: red">Note: </span> In the free version, signing keys are common for everyone, imposing a security risk. So, it is only recommended for POC purposes. In the <span style="color: blue">Premium </span>version keys will be dynamic for every configuration.</p>
				<br>
				<a type="button" href="?option=downloadsigningcertificate&client='.$currentclient->client_id.'" class="button button-primary button-large">Download Signing Certificate</a>
				<br/><br/>In case of RSA, you will need to provide the signing certificate (public key) to your client.
			</div>
		</div><br>
		<input type="hidden" name="option" value="mo_oauth_server_enable_jwt_support" />
		<input type="hidden" name="mo_oauth_server_appname" value="'.$currentclient->client_name.'" />
		<input type="submit" name="submit" value="Save Settings" class="button button-primary button-large" />
	</form>
</div>';

echo '<script>
		function showToast(element, checked, id) {
			element.checked = checked;
			var x = document.getElementById(id);
			x.classList.add("show");
			setTimeout(function(){ x.classList.remove("show") }, 6000);
        }

        function turnOff(element, id) {
            var sp = document.getElementById(id);
            if(element.checked != true) {
                sp.innerHTML = "";
            } else if(element.checked == true) {
                sp.innerHTML = "ON";
			}
			checkToShow(id);
		}

		function checkToShow(id) {
			if(id === "mo_server_jwt_toggle") {
				var x = document.getElementById("mo_server_enable_jwt_support_toggle");
				console.log(x);
				if(x.checked == true) {
					document.getElementById("mo_server_jwt_signing_algos").hidden = false;
					console.log("On");
				} else if(x.checked == false) {
					document.getElementById("mo_server_jwt_signing_algos").hidden = true;
					console.log("Off");
				}
			}
			if(id === "mo_server_client_auth_toggle") {
				var x = document.getElementById("mo_server_enable_private_key_jwt_client_auth_id");
				console.log(x);
				if(x.checked == true) {
					document.getElementById("mo_server_private_key_jwt_client_auth_cert").hidden = false;
					console.log("On");
				} else if(x.checked == false) {
					document.getElementById("mo_server_private_key_jwt_client_auth_cert").hidden = true;
					console.log("Off");
				}
			}
			return;
		}

	</script>';
	?>
<?php
} /**
 * Show Default Available Options
 *
 * Allows to select from default supported OAuth Client Applications.
 *
 * @return void
 **/
function mo_oauth_server_show_default_clients($client_object)
{
    wp_enqueue_script(
        "mo_oauth_server_admin_app_search_script",
        MOSERVER_URL . "admin/js/search_client.js",
        [],
        $ver = null,
        $in_footer = true
    ); ?>
<h3>Add Client<span style="float:right;"><?php mo_oauth_server_get_api_doc_link(
    $client_object
); ?></span></h3>
<input type="text" id="mo_oauth_server_default_clients_input" onkeyup="mo_oauth_server_default_clients_input_filter()" placeholder="Select client" title="Type in a Client Name">
<h3>OAuth Clients</h3>
<hr />
<ul id="mo_oauth_server_default_clients">
	<?php
 $defaultapps = file_get_contents(
     MOSERVER_DIR .
         "admin" .
         DIRECTORY_SEPARATOR .
         "partials" .
         DIRECTORY_SEPARATOR .
         "clients" .
         DIRECTORY_SEPARATOR .
         "partials" .
         DIRECTORY_SEPARATOR .
         "defaultclients.json",
     true
 );
 $defaultappsjson = json_decode($defaultapps);
 foreach ($defaultappsjson as $app_id => $application) {
     echo '<li data-appid="' .
         $app_id .
         '"><a href="#"><img class="mo_oauth_server_default_client_icon" src="' .
         MOSERVER_URL .
         "admin/partials/clients/images/" .
         $application->image .
         '"><br>' .
         $application->label .
         "</a></li>"; // phpcs:ignore WordPress.Security.EscapeOutput
 }?>
</ul>
<div id="mo_oauth_server_search_res"></div>
<script>
	jQuery("#mo_oauth_server_default_clients li").click(function() {
		var appId = jQuery(this).data("appid");
		window.location.href += "&action=add&clientId=" + appId;
	});
</script>
<?php
}
function mo_oauth_server_delete_app($appname, $clientid)
{
    $moOauthServerDb = new MoOauthServerDb();
    $clientlist = $moOauthServerDb->delete_client($appname, $clientid);
}
function mo_oauth_server_miniorange_support()
{
    ?>
<div id="mo_support_layout" class="mo_support_layout">
	<!--<h3>Support</h3>
		<div >
			<p>Your general questions can be asked in the plugin <a href="https://wordpress.org/support/plugin/miniorange-login-with-eve-online-google-facebook" target="_blank">support forum</a>.</p>
		</div>
		<div style="text-align:center;">
			<h4>OR</h4>
		</div>-->
	<div>
		<h3>Contact Us</h3>
		<p><?php esc_html_e(
      "Need any help? ",
      "miniorange-oauth-20-server"
  ); ?><br><?php esc_html_e(
    "Just send us a query so we can help you.",
    "miniorange-oauth-20-server"
); ?></p>
		<form method="post" action="">
			<input type="hidden" name="option" value="mo_oauth_contact_us_query_option" />
			<table class="mo_settings_table">
				<tr>
					<td><input type="email" class="mo_table_textbox" required style="width:90%" name="mo_oauth_contact_us_email" placeholder="Enter email here" value="<?php echo esc_attr(
         get_option("mo_oauth_admin_email")
     ); ?>"></td>
				</tr>
				<tr>
					<td><input type="tel" id="contact_us_phone" style="width:90%" pattern="[\+]\d{11,14}|[\+]\d{1,4}[\s]\d{9,10}" placeholder="Enter phone here" class="mo_table_textbox" name="mo_oauth_contact_us_phone" value="<?php echo esc_attr(
         get_option("mo_oauth_server_admin_phone")
     ); ?>"></td>
				</tr>
				<tr>
					<td><textarea class="mo_table_textbox" style="width:90%" onkeypress="mo_oauth_valid_query(this)" placeholder="Enter your query here" onkeyup="mo_oauth_valid_query(this)" onblur="mo_oauth_valid_query(this)" required name="mo_oauth_contact_us_query" rows="4" style="resize: vertical;"></textarea></td>
				</tr>
			</table>
			<div style="text-align:center;">
				<input type="submit" name="submit" style="margin:15px; width:100px;" class="button button-primary button-large" />
			</div>
			<p><?php esc_html_e(
       "If you want custom features in the plugin, just drop an email at ",
       "miniorange-oauth-20-server"
   ); ?><a href="mailto:oauthsupport@xecurify.com">oauthsupport@xecurify.com</a>.</p>
		</form>
	</div>
</div>

<script>
	jQuery("#contact_us_phone").intlTelInput();

	function mo_oauth_valid_query(f) {
		!(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
			/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
	}
</script>
<br />
<div class="mo_support_layout">
	<div>
		<p><b>Looking for user provisioning? </b><a href="https://plugins.miniorange.com/wordpress-user-provisioning">Click here</a> to know more about miniOrange SCIM User Provisioner Add-On.<br></p>
	</div>
</div>
<br />
<script type='text/javascript'>
	<!--//--><![CDATA[//><!--
	! function(a, b) {
		"use strict";

		function c() {
			if (!e) {
				e = !0;
				var a, c, d, f, g = -1 !== navigator.appVersion.indexOf("MSIE 10"),
					h = !!navigator.userAgent.match(/Trident.*rv:11\./),
					i = b.querySelectorAll("iframe.wp-embedded-content");
				for (c = 0; c < i.length; c++) {
					if (d = i[c], !d.getAttribute("data-secret")) f = Math.random().toString(36).substr(2, 10), d.src += "#?secret=" + f, d.setAttribute("data-secret", f);
					if (g || h) a = d.cloneNode(!0), a.removeAttribute("security"), d.parentNode.replaceChild(a, d)
				}
			}
		}
		var d = !1,
			e = !1;
		if (b.querySelector)
			if (a.addEventListener) d = !0;
		if (a.wp = a.wp || {}, !a.wp.receiveEmbedMessage)
			if (a.wp.receiveEmbedMessage = function(c) {
					var d = c.data;
					if (d)
						if (d.secret || d.message || d.value)
							if (!/[^a-zA-Z0-9]/.test(d.secret)) {
								var e, f, g, h, i, j = b.querySelectorAll('iframe[data-secret="' + d.secret + '"]'),
									k = b.querySelectorAll('blockquote[data-secret="' + d.secret + '"]');
								for (e = 0; e < k.length; e++) k[e].style.display = "none";
								for (e = 0; e < j.length; e++)
									if (f = j[e], c.source === f.contentWindow) {
										if (f.removeAttribute("style"), "height" === d.message) {
											if (g = parseInt(d.value, 10), g > 1e3) g = 1e3;
											else if (~~g < 200) g = 200;
											f.height = g
										}
										if ("link" === d.message)
											if (h = b.createElement("a"), i = b.createElement("a"), h.href = f.getAttribute("src"), i.href = d.value, i.host === h.host)
												if (b.activeElement === f) a.top.location.href = d.value
									} else;
							}
				}, d) a.addEventListener("message", a.wp.receiveEmbedMessage, !1), b.addEventListener("DOMContentLoaded", c, !1), a.addEventListener("load", c, !1)
	}(window, document);
	//--><!]]>
</script><iframe sandbox="allow-scripts" security="restricted" src="https://wordpress.org/plugins/miniorange-login-with-eve-online-google-facebook/embed/" width="100%" title="&#8220;OAuth Single Sign On &#8211; SSO (OAuth client)&#8221; &#8212; Plugin Directory" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" class="wp-embedded-content"></iframe>
<?php
}
function mo_oauth_server_requestforquote()
{
    $email = get_option("mo_oauth_admin_email");
    $phone = get_option("mo_oauth_server_admin_phone");
    if (array_key_exists("plan_name", $_REQUEST)) {
        if ($_REQUEST["plan_name"] == "lite_monthly") {
            $plan = "mo_lite_monthly";
            $plan_desc = "LITE PLAN - Monthly";
            $users = "5000+";
        } elseif ($_REQUEST["plan_name"] == "lite_yearly") {
            $plan = "mo_lite_yearly";
            $plan_desc = "LITE PLAN - Yearly";
            $users = "5000+";
        } elseif ($_REQUEST["plan_name"] == "wp_yearly") {
            $plan = "mo_wp_yearly";
            $plan_desc = "PREMIUM PLAN - Yearly";
            if ($sho_REQUEST["plan_users"] == "5K") {
                $users = "5000+";
            } else {
                $users = "Unlimited";
            }
        } elseif ($_REQUEST["plan_name"] == "all_inclusive") {
            $plan = "mo_all_inclusive";
            $plan_desc = "All Inclusive Plan";
            $users = "";
        }
    }
    if (isset($plan) || isset($users)) {
        $request_quote = "Any Special Requirements: ";
    } else {
        $request_quote = "";
    }
    echo '

	<div class="mo_idp_divided_layout mo-idp-full">
        <div class="mo_idp_table_layout mo-idp-center">
            <h2>SUPPORT</h2><hr>
            <p>Need any help? Just send us a query so we can help you.</p>
            <form method="post" action="">
                <input type="hidden" name="option" value="mo_oauth_contact_us_query_option" />
                <table class="mo_idp_settings_table">
                    <tr>
                        <td colspan=4>
                            <input  type="email" 
                                    class="mo_idp_table_contact" required 
                                    placeholder="Enter your Email" 
                                    name="mo_oauth_contact_us_email" 
                                    value="' .
        esc_attr($email) .
        '">
                        </td>
                    </tr>
                    <tr>
                        <td colspan=4>
                            <input  type="tel" 
                                    id="contact_us_phone" 
                                    pattern="[\+]\d{11,14}|[\+]\d{1,4}[\s]\d{9,10}" 
                                    placeholder="Enter your phone number with country code (+1)" 
                                    class="mo_idp_table_contact" 
                                    name="mo_oauth_contact_us_phone" 
                                    value="' .
        esc_attr($phone) .
        '">
                        </td>
                    </tr>';
    if (!empty($plan)) {
        echo '      <tr>
                        <td style="padding:10px; width: auto;">
                            <label for="plan-name-dd">Choose a plan:</label>
                        </td>
                        <td style="padding:10px; width: auto;">    
                            <select name="mo_idp_upgrade_plan_name" id="plan-name-dd">
                                <option value="lite_monthly"
                                ' .
            (!empty($plan) && strpos($plan, "lite_monthly") ? "selected" : "") .
            '>
                                    Cloud IDP Lite - Monthly Plan
                                </option>
                                <option value="lite_yearly"
                                ' .
            (!empty($plan) && strpos($plan, "lite_yearly") ? "selected" : "") .
            '>
                                    Cloud IDP Lite - Yearly Plan
                                </option>
                                <option value="wp_yearly"
                                ' .
            (!empty($plan) && strpos($plan, "wp_yearly") ? "selected" : "") .
            '>
                                    WordPress Premium - Yearly Plan
                                </option>
                                <option value="all_inclusive"
                                ' .
            (!empty($plan) && strpos($plan, "all_inclusive")
                ? "selected"
                : "") .
            '>
                                    All Inclusive Plan
                                </option>
                            </select>
                        </td>
                        <td style="padding:10px; width: auto;">
                            Number of users: 
                        </td>
                        <td style="padding:10px; width: auto;">    
                            <input  type="text"
                                    name="mo_idp_upgrade_plan_users"
                                    value="' .
            (!empty($users) ? esc_attr($users) : "") .
            '">
                        </td>
                    </tr>';
    }
    echo '          <tr>
                        <td colspan=4>
                            <textarea   class="mo_idp_table_contact" 
                                        onkeypress="mo_idp_valid_query(this)" 
                                        onkeyup="mo_idp_valid_query(this)" 
                                        placeholder="Write your query here" 
                                        onblur="mo_idp_valid_query(this)" required 
                                        name="mo_oauth_contact_us_query" 
                                        rows="4" 
                                        style="resize: vertical;">' .
        esc_attr($request_quote) .
        '</textarea>
                        </td>
                    </tr>
                </table>
                <br>
                <input  type="submit" 
                        name="submit" 
                        value="Submit Query" 
                        style="width:110px;" 
                        class="button button-primary button-large" />
    
            </form>
            <p>
                If you want custom features in the plugin, just drop an email to 
                <a href="mailto:oauthsupport@xecurify.com">oauthsupport@xecurify.com</a>.
            </p>
        </div>
    </div>
    
        <script>
            function moSharingSizeValidate(e){
                var t=parseInt(e.value.trim());t>60?e.value=60:10>t&&(e.value=10)
            }
            function moSharingSpaceValidate(e){
                var t=parseInt(e.value.trim());t>50?e.value=50:0>t&&(e.value=0)
            }
            function moLoginSizeValidate(e){
                var t=parseInt(e.value.trim());t>60?e.value=60:20>t&&(e.value=20)
            }
            function moLoginSpaceValidate(e){
                var t=parseInt(e.value.trim());t>60?e.value=60:0>t&&(e.value=0)
            }
            function moLoginWidthValidate(e){
                var t=parseInt(e.value.trim());t>1000?e.value=1000:140>t&&(e.value=140)
            }
            function moLoginHeightValidate(e){
                var t=parseInt(e.value.trim());t>50?e.value=50:35>t&&(e.value=35)
            }
        </script>';
}
function mo_oauth_server_get_api_doc_link($client_object)
{
    ?><a href="https://plugins.miniorange.com/oauth-api-documentation" target="_blank" class="mo-oauth-setup-guide-button" style="text-decoration: none;"><?php mo_oauth_server_get_doc_icon(); ?> API Documentation</a><?php if (
     !is_null($client_object) &&
     isset($client_object["doc"])
 ) { ?>
<a href="<?php echo esc_attr(
    $client_object["doc"]
); ?>" target="_blank" class="mo-oauth-setup-guide-button" style="text-decoration: none;"><?php mo_oauth_server_get_doc_icon(); ?>Setup Guide</a><?php }
}
function mo_oauth_server_get_doc_icon()
{
    echo '<svg width="16" height="16" style="margin-bottom:-3px;"fill="blue" viewBox="0 0 16 16">
  <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1h-4z"/>
</svg>';

}
// Security warning notice on admin page
function mo_oauth_server_security_warning_message() {

	// css
	wp_enqueue_style( 'mo_oauth_server_admin_security_notice', plugins_url( '/css/security_notice.css', __FILE__ ) );

	   // If on plugin page don't show global notice,
	   // Also don't show when mo_oauth_server_hide_security_warning_admin is set to 1 in wp_options
		$jwks_uri_hit_count = get_option("mo_oauth_server_jwks_uri_hit_count");
		$hide_security_notice_permanent = get_option("mo_oauth_server_hide_security_warning_admin");
		$hide_security_notice_temporary = get_option("mo_oauth_server_security_warning_remind_date") > time();

	   	if (!isset($_GET["page"]) && $jwks_uri_hit_count >=10 && !boolval($hide_security_notice_permanent) && !$hide_security_notice_temporary) {
			  ?><div class="notice notice-info mo_security_banner mo_security_banner-admin">
				<div style="display: block;" id="mo_main-security-warning-banner" class="mo_security_banner-content">
				<div class="mo_security_banner-header">miniOrange OAuth Server</div>
				<br>
				<?php
				$login_url = get_option("host_name") . "/moas/login";
				$username = get_option("mo_oauth_admin_email");
				$payment_url = get_option("host_name") . "/moas/initializepayment";
				echo'
				<form style="display:none;" id="admin_loginform" action="' . esc_url_raw($login_url) . '" target="_blank" method="post">
				<input type="email" name="username" value="' . esc_attr($username) . '" />
				<input type="text" name="redirectUrl" value="' . esc_url_raw($payment_url) . '" />
				<input type="text" name="requestOrigin" value="wp_oauth_server_enterprise_plan"  />
				</form>';
			?>	
				<script>
					function upgrade_plugin_dashboard_form() {
						jQuery("#admin_loginform").submit()
					}
				</script>
				<div class="mo_button-tab">
					<a class="mo_security-warning-contact button button-primary button-large" onclick="upgrade_plugin_dashboard_form()" >Upgrade Now</a>
					<button class="button button-primary button-large mo_security_banner-close-admin">X</button>
				</div>
				<div>
					<span class="mo_warning-icon dashicons dashicons-warning"></span>
					<b class="mo_security_warning">SECURITY RISK!</b>
					<br><br>
					<span class="mo_notice-important">You are at a Security Risk for the WordPress OAuth Server Plugin. It is because you are using the free version of the plugin for JWT Signing, where new keys are not generated for each configuration and are common for all users.</span>
				</div>
				</div>
				<form action="#" method="POST">
					<div style="display: none;" class="mo_security_banner-content" id="mo_security-warning-confirmation-admin">
					<p>Are you sure want to dismiss this warning?</p>
					<p>The free plugin will stay functional but remain subject to this risk</p>
					<input type="submit" name="mo_admin_security_dismiss" id="mo_admin_security_dismiss" class="button button-red button-large" value="Yes, Dismiss this warning"></input>
					<input type="submit" name="mo_admin_sw_remind_later" id="mo_admin_sw_remind_later" class="button button-primary button-large" value="Remind me later"></input>
					</div>
				</form>
			</div>
  
			<script>
				const security_warning_banner_close_admin = document.querySelector('.mo_security_banner-close-admin');
				security_warning_banner_close_admin.addEventListener('click', function() {
					const security_warning_confirm_admin = document.querySelector("#mo_security-warning-confirmation-admin");
					const main_security_warning_banner = document.querySelector("#mo_main-security-warning-banner");
					security_warning_confirm_admin.style.display = "block";
					main_security_warning_banner.style.display = "none";
				});
		
				const admin_sw_dismiss = document.querySelector("#mo_admin_security_dismiss");
				<?php if (array_key_exists("mo_admin_security_dismiss", $_POST)) {
					update_option("mo_oauth_server_hide_security_warning_admin", 1); ?>
					const security_warning_banner_admin = document.querySelector(".mo_security_banner-admin");
					security_warning_banner_admin.style.display = "none";
				<?php
				}
				if (array_key_exists("mo_admin_sw_remind_later", $_POST)) {
        
	                $current_timestamp = time();
	                $remind_timestamp = strtotime("+1 days", $current_timestamp);
	                update_option("mo_oauth_server_security_warning_remind_date", $remind_timestamp);
	                ?>
	                    const expiry_banner_admin = document.querySelector(".mo_security_banner-admin");
	                    expiry_banner_admin.style.display = "none";
	        
                <?php
                } ?>
				
			</script><?php
		}
 	
}
?>