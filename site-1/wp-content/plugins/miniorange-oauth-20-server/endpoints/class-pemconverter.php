<?php


class PemConverter {
    private $pem;
    private $values;

    public function __construct( $pem ) {
        $this->pem = $this->sanitize_pem( $pem );
    }

    public function unpack_pem() {
        $res = openssl_pkey_get_private( $this->pem );
        if ( false === $res ) {
            $res = openssl_pkey_get_public( $this->pem );
        }

        if ( false === $res ) {
            wp_send_json(
                [
                    'error'         => 'invalid_client_key',
                    'error_message' => 'Invalid Client Key',
                ],
                401
            );
        }
        $details = openssl_pkey_get_details( $res );
        $this->values['kty'] = 'RSA';
        $keys = [
            'n' => 'n',
            'e' => 'e',
            'd' => 'd',
            'p' => 'p',
            'q' => 'q',
            'dp' => 'dmp1',
            'dq' => 'dmq1',
            'qi' => 'iqmp',
        ];
        foreach ($details['rsa'] as $key => $value) {
            if (in_array($key, $keys)) {
                $value = $this->base64url_encode( $value );
                $this->values[ array_search( $key, $keys ) ] = $value;
            }
        }
        $this->values['use'] = 'sig';
    }

    public function sanitize_pem( $pem ) {
        preg_match_all('#(-.*-)#', $pem, $matches, PREG_PATTERN_ORDER);
        $ciphertext = preg_replace('#-.*-|\r|\n| #', '', $pem);
        $pem = $matches[0][0].PHP_EOL;
        $pem .= chunk_split($ciphertext, 64, PHP_EOL);
        $pem .= $matches[0][1].PHP_EOL;
        return $pem;
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public function get_values() {
        return $this->values;
    }
}