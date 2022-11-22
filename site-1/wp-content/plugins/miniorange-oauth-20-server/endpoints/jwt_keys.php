<?php
include "class-pemconverter.php";

function _mo_jwt_keys( $data ) {
    mo_oauth_server_init();
    
    $client_id = isset( $data['client_id'] ) ? $data['client_id'] : false;
    if ( ! $client_id ) {
        wp_send_json(
            [
                'error'             => 'invalid_request',
                'error_description' => 'Resource Identifier Missing.',
            ],
            400
        );
    }
    global $wpdb;
    $myrows = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM ' .
            $wpdb->base_prefix . 
            'moos_oauth_clients WHERE client_id = \'%s\'',
            $client_id
            )
    );
    $client_exists = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT * FROM ' .
            $wpdb->base_prefix .
            'moos_oauth_public_keys WHERE client_id = \'%s\'',
            $myrows[0]->client_id
        ),
        ARRAY_A
    );
    if ( !is_array( $client_exists ) || empty( $client_exists ) || 1 > count( $client_exists ) || explode( 'S', $client_exists['encryption_algorithm'] )[0] === 'H' ) {
        header( 'Content-Type: application/json' );
        http_response_code( 400 );
        echo json_encode(
            [
                'error'         => 'invalid_client',
                'error_message' => 'Either Client does not exist or Signing algorithm is HSA based.',
            ],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        exit();
    }
    $public_key = $client_exists['public_key'];
    $pem_converter = new PemConverter( $public_key );
    $pem_converter->unpack_pem();
    header( 'Content-Type: application/json' );
    $values = $pem_converter->get_values();
    $values['alg'] = isset( $client_exists['encryption_algorithm'] ) ? $client_exists['encryption_algorithm'] : 'RS256';
    $values['kid'] = $client_id;
    http_response_code( 200 );
    echo json_encode(
        ['keys' => [$values]],
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    $jwks_uri_hit_count = get_option("mo_oauth_server_jwks_uri_hit_count");
    if($jwks_uri_hit_count === false){
        $jwks_uri_hit_count = 1;
    } else{
        $jwks_uri_hit_count++;
    }
    update_option('mo_oauth_server_jwks_uri_hit_count', $jwks_uri_hit_count, false);
    exit();
}