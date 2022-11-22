<?php

class MoOauthServerDb
{

	function mo_plugin_activate()
	{
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_clients (client_name VARCHAR(255), client_id VARCHAR(255), client_secret VARCHAR(255), redirect_uri VARCHAR(255), active_oauth_server_id INT);";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_access_tokens (access_token VARCHAR(255), client_id VARCHAR(255), user_id INT, expires TIMESTAMP, scope VARCHAR(255));";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_authorization_codes (authorization_code VARCHAR(255), client_id VARCHAR(255), user_id INT, redirect_uri VARCHAR(255), expires TIMESTAMP, scope VARCHAR(255), id_token VARCHAR(255));";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_refresh_tokens (refresh_token VARCHAR(255), client_id VARCHAR(255), user_id INT, expires TIMESTAMP, scope VARCHAR(255));";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_scopes (scope varchar(100), is_default BOOLEAN, UNIQUE (scope));";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_users (username VARCHAR(255) NOT NULL, password VARCHAR(2000), first_name VARCHAR(255), last_name VARCHAR(255), CONSTRAINT username_pk PRIMARY KEY (username));";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_public_keys (client_id VARCHAR(80), public_key VARCHAR(8000), private_key VARCHAR(8000), encryption_algorithm VARCHAR(80) DEFAULT 'RS256');";
		$wpdb->query($sql);
		$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."moos_oauth_authorized_apps (client_id TEXT, user_id INT);";
		$wpdb->query($sql);
		$wpdb->query("INSERT IGNORE INTO ".$wpdb->base_prefix."moos_oauth_scopes values('email', 1), ('profile', 0);");

		// check if the table moos_oauth_clients is already exist
		$table_name = $wpdb->base_prefix."moos_oauth_clients";
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			$sql = $wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = %s AND table_name = %s AND column_name ='active_oauth_server_id'",array(DB_NAME,$table_name)); 
			$row = $wpdb->get_results( $sql , ARRAY_A);
			if ( empty($row) ) {
				$sql = $wpdb->prepare("ALTER TABLE ".$wpdb->base_prefix."moos_oauth_clients ADD active_oauth_server_id INT DEFAULT %d",array(get_current_blog_id()));
				$wpdb->query($sql);
			}
		}
	}

	function add_client($client_name, $client_secret, $redirect_url,$active_oauth_server_id)
	{
		global $wpdb;
		$client_id = moosGenerateRandomString(32);
		$sql = $wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."moos_oauth_clients (client_name, client_id, client_secret, redirect_uri,active_oauth_server_id ) VALUES (%s, %s, %s, %s, %d )",array($client_name,$client_id,$client_secret,$redirect_url,$active_oauth_server_id));
		$wpdb->query($sql);

		// Storing client secret as private key in public keys table for HS algorithm
		$wpdb->query("INSERT INTO ".$wpdb->base_prefix."moos_oauth_public_keys (client_id, public_key, private_key, encryption_algorithm) VALUES ('".$client_id."', '', '".$client_secret."', 'HS256')");
	}

	function update_client($client_name, $redirect_url)
	{
		global $wpdb;
		$sql = $wpdb->prepare("UPDATE ".$wpdb->base_prefix."moos_oauth_clients SET redirect_uri = %s WHERE client_name = %s and active_oauth_server_id= %d",array($redirect_url,$client_name,get_current_blog_id()));
		$wpdb->query($sql);
	}

	function get_clients()
	{
		global $wpdb;
		$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."moos_oauth_clients where active_oauth_server_id= %d",array(get_current_blog_id()));
		$myrows = $wpdb->get_results($sql);
		return $myrows;
	}

	function delete_client($client_name, $client_id)
	{
		global $wpdb;
		// Deleting public and private keys for JWT support
		$sql = $wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."moos_oauth_public_keys WHERE client_id = %s",array($client_id));
		$wpdb->query($sql);
		
		$sql = $wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."moos_oauth_clients WHERE client_name = %s and active_oauth_server_id= %d",array($client_name,get_current_blog_id()));
		$wpdb->query($sql);
		delete_option('mo_oauth_server_client');
		delete_option('mo_oauth_server_enable_jwt_support_for_'.$client_name);
		delete_option('mo_oauth_server_jwt_signing_algo_for_'.$client_name);
	}

}