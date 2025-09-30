<?php


namespace Utils\Tracker\Google;

use GuzzleHttp\Client;

class GoogleAuth {
    private $clientEmail;
    private $privateKey;
    private $scopes = ['https://www.googleapis.com/auth/webmasters.readonly'];

    public function __construct($jsonKeyFile) {
        $data = json_decode(file_get_contents($jsonKeyFile), true);
        $this->clientEmail = $data['client_email'];
        $this->privateKey  = $data['private_key'];
    }

    public function getAccessToken() {
        $jwt = $this->createJwt();
        $client = new Client();

        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]
        ]);

        $data = json_decode((string)$response->getBody(), true);
        return $data['access_token'] ?? null;
    }

    private function createJwt() {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $claim = [
            'iss'   => $this->clientEmail,
            'scope' => implode(' ', $this->scopes),
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $segments = [];
        foreach ([$header, $claim] as $part) {
            $segments[] = rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');
        }
        $input = implode('.', $segments);

        openssl_sign($input, $signature, $this->privateKey, 'sha256WithRSAEncryption');
        $segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return implode('.', $segments);
    }
}
