<?php

require_once MOSERVER_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

include 'discovery.php';
include 'jwt_keys.php';

use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Server as OAuth2Server;
use OAuth2\Storage\Pdo;
use OAuth2\Storage\Memory;
use OAuth2\OpenID\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\Response;
use OAuth2\Request;

/**
 * Function to get default routes or OAuth Endpoints.
 */
function mo_oauth_server_get_default_routes()
{
    $default_routes = [
        'token' => [
            'methods'  => 'POST',
            'callback' => 'mo_oauth_server_token',
            'permission_callback' => '__return_true'
        ],
        'resource' => [
            'methods'  => 'GET',
            'callback' => 'mo_oauth_server_resource',
            'permission_callback' => '__return_true'
        ]
    ];
    return $default_routes;
}

/**	
 * Function to get OpenID Well-Known routes.	
 */
function mo_get_well_known_routes()
{
    $well_known_routes = [
        'openid-configuration' => [
            'methods'  => 'GET',
            'callback' => '_mo_discovery',
            'permission_callback' => '__return_true'
        ],
        'keys'                 => [
            'methods'  => 'GET',
            'callback' => '_mo_jwt_keys',
            'permission_callback' => '__return_true'
        ],
    ];
    return $well_known_routes;
}


function mo_oauth_server_init()
{
    $master_switch = (bool) get_option('mo_oauth_server_master_switch') ? get_option('mo_oauth_server_master_switch') : 'on';
    if ($master_switch === 'off') {
        wp_die("Currently your OAuth Server is not responding to any API request, please contact your site administrator.<br><b>ERROR:</b> ERR_MSWITCH");
    }

    if (!file_exists($sqliteFile = MOSERVER_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'oauth.sqlite')) {
        include_once(MOSERVER_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rebuild_db.php');
    }

    $storage = new Pdo(array('dsn' => 'sqlite:' . $sqliteFile));

    // create array of supported grant types
    $grantTypes = array(
        'authorization_code' => new AuthorizationCode($storage),
        'user_credentials'   => new UserCredentials($storage),
        'refresh_token'      => new RefreshToken($storage, array(
            'always_issue_new_refresh_token' => true,
        )),
    );

    $enforce_state = (bool) get_option('mo_oauth_server_enforce_state') ? get_option('mo_oauth_server_enforce_state') : 'on';
    $enable_oidc = (bool) get_option('mo_oauth_server_enable_oidc') ? get_option('mo_oauth_server_enable_oidc') : 'on';
    // instantiate the oauth server
    $config = [
        'enforce_state' => true,
        'allow_implicit' => false,
        'use_openid_connect' => ($enable_oidc === 'on'),
        'access_lifetime'        => get_option('mo_oauth_expiry_time') ? get_option('mo_oauth_expiry_time') : 3600,
        'refresh_token_lifetime' => get_option('mo_oauth_refresh_expiry_time') ? get_option('mo_oauth_refresh_expiry_time') : 1209600,
        'issuer' => site_url() . '/wp-json/moserver',
    ];
    $server = new OAuth2Server($storage, $config, $grantTypes);
    return $server;
}


function mo_oauth_server_authorize()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $authorize_url = network_site_url() . '/wp-json/moserver/authorize';
    $request_path = parse_url($_SERVER['REQUEST_URI']);
    $request_url = $protocol . $_SERVER['HTTP_HOST'] . $request_path["path"];
    if (!(strcmp($request_url, $authorize_url))) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            mo_oauth_server_validate_authorize_consent();
            exit();
        }
        $request = Request::createFromGlobals();
        $response = new Response();
        $server = mo_oauth_server_init();

        if (!$server->validateAuthorizeRequest($request, $response)) {
            $response->send();
            exit;
        }
        $prompt = $request->query('prompt') ? $request->query('prompt') : 'consent';
        if (!$request->query('ignore_prompt') && $prompt) {
            if ($prompt == 'login') {
                wp_logout();
                $actual_link = mo_oauth_server_get_current_page_url();
                wp_redirect(site_url() . "/wp-login.php?redirect_to=" . urlencode(str_replace('prompt=login', 'prompt=consent', $actual_link)));
                exit();
            }
        }
        $current_user = mo_oauth_server_check_user_login($request->query('client_id'));
        if (!$current_user) {
            $actual_link = mo_oauth_server_get_current_page_url();
            wp_redirect(site_url() . "/wp-login.php?redirect_to=" . urlencode($actual_link));
            exit;
        }

        $prompt_grant = 'on';
        $is_authorized = true;
        $client_id = $request->query('client_id');
        $grant_status = is_null($client_id) ? false : get_user_meta($current_user->ID, 'mo_oauth_server_granted_' . $client_id, true);
        $prompt       = ('allow' === $grant_status && $request->query('prompt') !== 'consent') || ('deny' === $grant_status && 'allow' === $prompt) ? 'allow' : 'consent';
        if ('allow' === $prompt) {
            $grant_status = 'allow';
        }
        if ($grant_status == 'allow' && $prompt !== 'consent') {
            $is_authorized = true;
        } elseif ($grant_status == 'deny' && $prompt !== 'consent') {
            $is_authorized = false;
        } elseif ($grant_status === false || $prompt === 'consent') {
            $client_credentials = $server->getStorage('client_credentials')->getClientDetails($request->query('client_id'));
            mo_oauth_server_render_consent_screen($client_credentials);
            exit();
        }
        $server->handleAuthorizeRequest($request, $response, $is_authorized, $current_user->ID);
        update_user_meta($current_user->ID, 'mo_oauth_server_granted_' . $client_id, 'deny');
        $response->send();
        exit();
    }
}

function mo_oauth_server_token()
{
    ob_end_clean();
    if (isset($_POST['grant_type'])) {
        $grant          = sanitize_text_field($_POST['grant_type']);
        $allowed_grants = ['authorization_code'];
        if (!in_array($grant, $allowed_grants)) {
            wp_send_json([
                'error' => 'invalid_grant',
                'error_description' => 'The "grant_type" requested is unsupported or invalid',
            ], 400);
        }
    }
    $request = Request::createFromGlobals();
    $server = mo_oauth_server_init();
    mo_oauth_server_set_allow_origin($request);
    $server->handleTokenRequest($request)->send();
    exit;
}

function mo_oauth_server_resource()
{
    $request = Request::createFromGlobals();
    $response = new Response();
    $server = mo_oauth_server_init();

    if (!$server->verifyResourceRequest($request, $response)) {
        $response = $server->getResponse();
        $response->send();
        exit();
    }
    $token = $server->getAccessTokenData($request, $response);
    $user_info = mo_oauth_server_get_token_user_info($token);
    if (is_null($user_info) || empty($user_info)) {
        wp_send_json(
            [
                'error' => 'invalid_token',
                'desc'  => 'access_token provided is either invalid or does not belong to a valid user.'
            ],
            403
        );
    }
    $api_response = [
        'ID' => $user_info->ID,
        'username' => $user_info->user_login,
        'email' => $user_info->user_email,
        'first_name' => $user_info->first_name,
        'last_name' => $user_info->last_name,
        'nickname' => $user_info->nickname,
        'display_name' => $user_info->display_name
    ];
    return $api_response;
}

function mo_oauth_server_logged_user_from_auth_cookie()
{
    if (!is_user_logged_in())
        return false;

    $auth_cookie = wp_parse_auth_cookie('', 'logged_in');
    if (!$auth_cookie || is_wp_error($auth_cookie) || !$auth_cookie['token'] || !$auth_cookie['username']) {
        return false;
    }
    if (wp_get_current_user()->user_login == $auth_cookie['username'])
        return $auth_cookie;
    return false;
}

function mo_oauth_server_get_token_user_info($token = null)
{
    if ($token === null || !isset($token['user_id'])) {
        return [];
    }
    $user_info = get_userdata($token['user_id']);
    if (null === $user_info) {
        return [];
    }
    return $user_info;
}

function mo_oauth_server_check_user_login($client_id)
{
    $current_user_cookie = mo_oauth_server_logged_user_from_auth_cookie();
    if (!$current_user_cookie) {
        return false;
    }
    global $wpdb;
    $sql = $wpdb->prepare("SELECT active_oauth_server_id FROM " . $wpdb->base_prefix . "moos_oauth_clients WHERE client_id = %s", array(sanitize_text_field($_GET['client_id'])));
    $server_details = $wpdb->get_results($sql);

    if ($server_details == NULL) {
        wp_die("Your client id is invalid. Please contact to your administrator.");
        exit();
    }

    $user = get_user_by('login', $current_user_cookie['username']);
    $is_user_member_of_blog = is_user_member_of_blog($user->ID, $server_details[0]->active_oauth_server_id);
    if ($is_user_member_of_blog === false) {
        wp_logout();
        wp_die("Invalid credentials. Please contact to your administrator.");
    }

    return $user;
}

function mo_oauth_server_render_consent_screen($client_credentials)
{
    $authorize_dialog_template = MOSERVER_DIR . DIRECTORY_SEPARATOR . 'endpoints' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'authorize_dialog.php';
    $authorize_dialog_template = apply_filters('mo_oauth_server_authorize_dialog_template_path', $authorize_dialog_template);
    header('Content-Type: text/html');
    include $authorize_dialog_template;
    if (function_exists('mo_oauth_server_emit_html')) {
        mo_oauth_server_emit_html($client_credentials);
    }
    exit();
}

function mo_oauth_server_validate_authorize_consent()
{
    $user = mo_oauth_server_check_user_login(sanitize_text_field($_REQUEST['client_id']));
    if (isset($_POST['authorize-dialog'])) {
        if (wp_verify_nonce($_POST['nonce'], 'mo-oauth-server-authorize-dialog')) {
            $response = sanitize_text_field($_POST['authorize']);
            update_user_meta($user->ID, 'mo_oauth_server_granted_' . sanitize_text_field($_REQUEST['client_id']), $response);
        }
        $current_url = explode('?', mo_oauth_server_get_current_page_url())[0];
        $_GET['prompt'] = $response;
        wp_safe_redirect($current_url . '?' . http_build_query($_GET));
        exit();
    }
}

function mo_oauth_server_get_current_page_url()
{
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function mo_oauth_server_set_allow_origin($request)
{
    if (isset($request->request['redirect_uri'])) {
        $redirect_uri = explode('/', $request->request['redirect_uri']);
        header('Access-Control-Allow-Origin:' . $redirect_uri[0] . '//' . $redirect_uri[2]);
    }
}