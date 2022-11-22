<?php
/** miniOrange enables user to log in into their OAuth/OpenID Connect applications through WordPress users.
    Copyright (C) 2015  miniOrange

	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
		
	* @package 		miniOrange OAuth
	* @license		https://docs.miniorange.com/mit-license MIT/Expat
*/

/**
	This library is miniOrange Authentication Service. 
	Contains Request Calls to Customer service.

**/
class Mo_Auth_Server_Customer {

	public $email;
	public $phone;

	private $defaultCustomerKey = "16555";
	private $defaultApiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";

	function create_customer(){
		$url = get_option('host_name') . '/moas/rest/customer/add';
		$this->email 		= get_option('mo_oauth_admin_email');
		$this->phone 		= get_option('mo_oauth_server_admin_phone');
		$password 			= get_option('password');
		$firstName    		= get_option('mo_oauth_admin_fname');
		$lastName     		= get_option('mo_oauth_admin_lname');
		$company      		= get_option('mo_oauth_admin_company');

		$fields = array(
			'companyName' => $company,
			'areaOfInterest' => 'WP OAuth 2.0 Server',
			'firstname'	=> $firstName,
			'lastname'	=> $lastName,
			'email'		=> $this->email,
			'phone'		=> $this->phone,
			'password'	=> $password
		);
		$field_string = json_encode($fields);
		$headers = array( 'Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic' );
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
 
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ".esc_attr($error_message);
			exit();
		}
		
		return wp_remote_retrieve_body($response);
	}

	public function get_timestamp() {
		$url = get_option ( 'host_name' ) . '/moas/rest/mobile/get-timestamp';
		
		$headers = array( 'Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic' );
		$args = array(
			'method' =>'POST',
			'body' => array(),
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
 
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
		
		return wp_remote_retrieve_body($response);

	}

	function mo_oauth_send_email_alert($email,$phone,$message){

		$url = get_option( 'host_name' ) . '/moas/api/notify/send';
		
		$customerKey = $this->defaultCustomerKey;
		$apiKey =  $this->defaultApiKey;

		$currentTimeInMillis = self::get_timestamp();
		$stringToHash 		= $customerKey .  $currentTimeInMillis . $apiKey;
		$hashValue 			= hash("sha512", $stringToHash);
		$customerKeyHeader 	= "Customer-Key: " . $customerKey;
		$timestampHeader 	= "Timestamp: " .  $currentTimeInMillis;
		$authorizationHeader= "Authorization: " . $hashValue;
		$fromEmail 			= $email;
		$subject            = "Feedback: WordPress OAuth Server Plugin";
		$site_url=site_url();

		global $user;
		$user         = wp_get_current_user();
		$query        = '[WordPress WP OAuth Server] : ' . $message;

		$content='<div >Hello, <br><br>First Name :'.esc_attr($user->user_firstname).'<br><br>Last  Name :'.$user->user_lastname.'   <br><br>Company :<a href="'.esc_attr($_SERVER['SERVER_NAME']).'" target="_blank" >'.esc_attr($_SERVER['SERVER_NAME']).'</a><br><br>Phone Number :'.esc_attr($phone).'<br><br>Email :<a href="mailto:'.esc_attr($fromEmail).'" target="_blank">'.esc_attr($fromEmail).'</a><br><br>Query :'.esc_attr($query).'</div>';

		$fields = array(
			'customerKey'	=> $customerKey,
			'sendEmail' 	=> true,
			'email' 		=> array(
				'customerKey' 	=> $customerKey,
				'fromEmail' 	=> $fromEmail,
				'bccEmail' 		=> 'oauthsupport@xecurify.com',
				'fromName' 		=> 'miniOrange',
				'toEmail' 		=> 'oauthsupport@xecurify.com',
				'toName' 		=> 'oauthsupport@xecurify.com',
				'subject' 		=> $subject,
				'content' 		=> $content
			),
		);
		$field_string = json_encode($fields);

		$headers = array( 'Content-Type' => 'application/json');
		$headers['Customer-Key'] = $customerKey;
		$headers['Timestamp'] = $currentTimeInMillis;
		$headers['Authorization'] = $hashValue;
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
 
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
		
		return wp_remote_retrieve_body($response);
	}

	function get_customer_key() {
		$url 	= get_option('host_name') . "/moas/rest/customer/key";
		$email 	= get_option("mo_oauth_admin_email");

		$password 			= get_option("password");

		$fields = array(
			'email' 	=> $email,
			'password' 	=> $password
		);
		$field_string = json_encode( $fields );

		$headers = array( 'Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic' );
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
 
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
		
		return wp_remote_retrieve_body($response);
	}


	function submit_contact_us( $email, $phone, $query ) {
		global $current_user;
		wp_get_current_user();
		$query = '[WP OAuth 2.0 Server] ' . $query;
		$fields = array(
			'firstName'			=> $current_user->user_firstname,
			'lastName'	 		=> $current_user->user_lastname,
			'company' 			=> sanitize_text_field($_SERVER['SERVER_NAME']),
			'email' 			=> $email,
			'ccEmail'           => 'oauthsupport@xecurify.com',
			'phone'				=> $phone,
			'query'				=> $query
		);
		$field_string = json_encode( $fields );

		$url = get_option('host_name') . '/moas/rest/customer/contact-us';
		$headers = array( 'Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic' );
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
 
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
		
		return wp_remote_retrieve_body($response);
	}

	// Added Trials Available function
	function mo_oauth_send_demo_alert($email,$demo_plan,$message,$subject) {

		$url = get_option( 'host_name' ) . '/moas/api/notify/send';
		
		$customerKey = $this->defaultCustomerKey;
		$apiKey =  $this->defaultApiKey;

		$currentTimeInMillis = self::get_timestamp();
		$stringToHash 		= $customerKey .  $currentTimeInMillis . $apiKey;
		$hashValue 			= hash("sha512", $stringToHash);
		$customerKeyHeader 	= "Customer-Key: " . $customerKey;
		$timestampHeader 	= "Timestamp: " .  $currentTimeInMillis;
		$authorizationHeader= "Authorization: " . $hashValue;
		$fromEmail 			= $email;
		$site_url=site_url();

		global $user;
		$user         = wp_get_current_user();

		$content='<div >Hello, </a><br><br>Email :<a href="mailto:'. $fromEmail.'" target="_blank">'.$fromEmail.'</a><br><br>Requested Demo for     : ' . $demo_plan . '<br><br>Requirements (User usecase)           : ' . $message.'</div>';

		$fields = array(
			'customerKey'	=> $customerKey,
			'sendEmail' 	=> true,
			'email' 		=> array(
				'customerKey' 	=> $customerKey,
				'fromEmail' 	=> $fromEmail,
				'bccEmail' 		=> 'oauthsupport@xecurify.com',
				'fromName' 		=> 'miniOrange',
				'toEmail' 		=> 'oauthsupport@xecurify.com',
				'toName' 		=> 'oauthsupport@xecurify.com',
				'subject' 		=> $subject,
				'content' 		=> $content
			),
		);
		$field_string = json_encode($fields);
		$headers = array( 'Content-Type' => 'application/json');
		$headers['Customer-Key'] = $customerKey;
		$headers['Timestamp'] = $currentTimeInMillis;
		$headers['Authorization'] = $hashValue;
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,

		);
		
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
	}

	// Added Request for jwks hits more than 10 times
	function mo_oauth_send_jwks_alert($email, $message, $subject) {

		$url = get_option( 'host_name' ) . '/moas/api/notify/send';
		
		$customerKey = $this->defaultCustomerKey;
		$apiKey =  $this->defaultApiKey;

		$currentTimeInMillis = self::get_timestamp();
		$stringToHash 		= $customerKey .  $currentTimeInMillis . $apiKey;
		$hashValue 			= hash("sha512", $stringToHash);
		$customerKeyHeader 	= "Customer-Key: " . $customerKey;
		$timestampHeader 	= "Timestamp: " .  $currentTimeInMillis;
		$authorizationHeader= "Authorization: " . $hashValue;
		$fromEmail 			= $email;
		$site_url=site_url();

		global $user;
		$user         = wp_get_current_user();

		$content='<div >'. $message.'</div>';

		$fields = array(
			'customerKey'	=> $customerKey,
			'sendEmail' 	=> true,
			'email' 		=> array(
				'customerKey' 	=> $customerKey,
				// 'fromEmail' 	=> $fromEmail,
				'bccEmail' 		=> 'oauthsupport@xecurify.com',
				'fromName' 		=> 'miniOrange',
				'toEmail' 		=> $email,
				'toName' 		=> $email,
				'subject' 		=> $subject,
				'content' 		=> $content
			),
		);
		$field_string = json_encode($fields);
		$headers = array( 'Content-Type' => 'application/json');
		$headers['Customer-Key'] = $customerKey;
		$headers['Timestamp'] = $currentTimeInMillis;
		$headers['Authorization'] = $hashValue;
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,

		);
		
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: ". esc_attr($error_message);
			exit();
		}
	}

	function check_customer() {
			$url 	= get_option('host_name') . "/moas/rest/customer/check-if-exists";
			$email 	= get_option("mo_oauth_admin_email");

			$fields = array(
				'email' 	=> $email,
			);
			$field_string = json_encode( $fields );

			$headers = array( 'Content-Type' => 'application/json', 'charset' => 'UTF - 8', 'Authorization' => 'Basic' );
			$args = array(
				'method' =>'POST',
				'body' => $field_string,
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => $headers,
	
			);
			
			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo "Something went wrong: ". esc_attr($error_message);
				exit();
			}

			return wp_remote_retrieve_body($response);
	}

	// function to send an alert regarding video demo data
	function mo_oauth_send_video_demo_alert($email,$ist_date,$query,$ist_time,$subject,$call_time_zone,$call_time,$call_date) {

		if(!$this->check_internet_connection())
			return;
		
		$url = get_option( 'host_name' ) . '/moas/api/notify/send';
		
		$customerKey = $this->defaultCustomerKey;
		$apiKey =  $this->defaultApiKey;
	
		$currentTimeInMillis = self::get_timestamp();
		$stringToHash 		= $customerKey .  $currentTimeInMillis . $apiKey;
		$hashValue 			= hash("sha512", $stringToHash);
		$customerKeyHeader 	= "Customer-Key: " . $customerKey;
		$timestampHeader 	= "Timestamp: " .  $currentTimeInMillis;
		$authorizationHeader= "Authorization: " . $hashValue;
		$fromEmail 			= $email;
		$site_url=site_url();
	
		global $user;
		$user         = wp_get_current_user();
		$content='<div >Hello, </a><br><br>Email :<a href="mailto:'. $fromEmail.'" target="_blank">'.$fromEmail.'</a><br><br> Customer local time ('.$call_time_zone.') : '.$call_time.' on '.$call_date.'<br><br>IST format    : ' . $ist_time . ' on ' . $ist_date . '<br><br>Requirements (User usecase)           : ' . $query.'</div>';
		
		$fields = array(
			'customerKey'	=> $customerKey,
			'sendEmail' 	=> true,
			'email' 		=> array(
				'customerKey' 	=> $customerKey,
				'fromEmail' 	=> $fromEmail,
				'bccEmail' 		=> 'oauthsupport@xecurify.com',
				'fromName' 		=> 'miniOrange',
				'toEmail' 		=> 'oauthsupport@xecurify.com',
				'toName' 		=> 'oauthsupport@xecurify.com',
				'subject' 		=> $subject,
				'content' 		=> $content
			),
		);
		$field_string = json_encode($fields);
		$headers = array( 'Content-Type' => 'application/json');
		$headers['Customer-Key'] = $customerKey;
		$headers['Timestamp'] = $currentTimeInMillis;
		$headers['Authorization'] = $hashValue;
		$args = array(
			'method' =>'POST',
			'body' => $field_string,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => $headers,
	
		);
		
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo 'Something went wrong: ' . esc_attr( $error_message );
			exit();
		}
	}

	// function to check internet connection
	function check_internet_connection() {
		return (bool) @fsockopen('login.xecurify.com', 443, $iErrno, $sErrStr, 5);
	}

}?>
