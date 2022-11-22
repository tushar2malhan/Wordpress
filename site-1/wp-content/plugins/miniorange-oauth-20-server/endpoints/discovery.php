<?php

function _mo_discovery($data)
{
    mo_oauth_server_init();     // checking either server is on or off
    $client_id = isset($data['client_id']) ? $data['client_id'] : false;
    if (!$client_id) {
        wp_send_json(
            [
                'error'             => 'invalid_request',
                'error_description' => 'Resource Identifier Missing.',
            ],
            400
        );
    }
    return [
        'request_parameter_supported'                      => true,
        'claims_parameter_supported'                       => false,
        'issuer'                                           => network_site_url() . '/wp-json/moserver/' . $client_id,
        'authorization_endpoint'                           => network_site_url() . '/wp-json/moserver/authorize',
        'token_endpoint'                                   => network_site_url() . '/wp-json/moserver/token',
        'userinfo_endpoint'                                => network_site_url() . '/wp-json/moserver/resource',
        'scopes_supported'                                 => ['profile', 'openid', 'email'],
        'id_token_signing_alg_values_supported'            => ['HS256', 'RS256'],
        'response_types_supported'                         => ['code'],
        'jwks_uri'                                         => network_site_url() . '/wp-json/moserver/' . $client_id . '/.well-known/keys',
        'grant_types_supported'                            => ['authorization_code'],
        'subject_types_supported'                          => ['public'],
    ];
}
