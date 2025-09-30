<?php

namespace Utils\Tracker\Google;

use GuzzleHttp\Client;

class GoogleSearchConsole {
    private $clientId;
    private $clientSecret;
    private $optionName = 'halteverbot_google_search_console';
    private $httpClient;

    public function __construct($clientId = null, $clientSecret = null) {
        $options = $this->getOptions();
        $this->clientId = $clientId ?: ($options['client_id'] ?? '');
        $this->clientSecret = $clientSecret ?: ($options['client_secret'] ?? '');
        $this->httpClient = new Client();
    }

    /**
     * Getter für Client ID
     */
    public function getClientId() {
        return $this->clientId;
    }

    /**
     * Getter für Client Secret
     */
    public function getClientSecret() {
        return $this->clientSecret;
    }

    /**
     * Speichert Client ID und Secret in Datenbank
     */
    public function saveCredentials($clientId, $clientSecret) {
        $options = $this->getOptions();
        $options['client_id'] = sanitize_text_field($clientId);
        $options['client_secret'] = sanitize_text_field($clientSecret);
        
        update_option($this->optionName, $options);
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        
        return true;
    }

    /**
     * Lädt Optionen aus Datenbank
     */
    public function getOptions() {
        return get_option($this->optionName, [
            'client_id' => '',
            'client_secret' => '',
            'token_data' => null
        ]);
    }

    /**
     * Speichert Token in Datenbank
     */
    private function saveToken($tokenData) {
        $options = $this->getOptions();
        $tokenData['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);
        $options['token_data'] = $tokenData;
        update_option($this->optionName, $options);
        return true;
    }

    /**
     * Lädt Token aus Datenbank
     */
    private function loadToken() {
        $options = $this->getOptions();
        return $options['token_data'] ?? null;
    }

    /**
     * Erzeugt die Auth-URL mit aktueller URL
     */
    public function getAuthUrl() {
        if (empty($this->clientId)) {
            return false;
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getCurrentUrl(),
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }

    /**
     * Holt aktuelle URL ohne OAuth-Parameter
     */
    public function getCurrentUrl() {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // Entferne alle OAuth-relevanten Parameter
        $params_to_remove = ['code', 'state', 'error', 'error_description', 'scope'];
        
        foreach ($params_to_remove as $param) {
            $url = remove_query_arg($param, $url);
        }
        
        // Entferne auch Anker (#) falls vorhanden
        $url = strtok($url, '#');
        
        return $url;
    }

    /**
     * Detaillierte Debug-Methode
     */
    public function authenticate($authCode) {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return [
                'success' => false, 
                'error' => 'Client ID oder Secret nicht konfiguriert'
            ];
        }

        $redirectUrl = $this->getCurrentUrl();

        // Detailliertes Debugging
        $debugInfo = [
            'client_id' => $this->clientId,
            'client_secret_length' => strlen($this->clientSecret),
            'code_length' => strlen($authCode),
            'redirect_uri' => $redirectUrl,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        error_log('=== GOOGLE OAUTH DEBUG START ===');
        error_log('Client ID: ' . $debugInfo['client_id']);
        error_log('Client Secret Length: ' . $debugInfo['client_secret_length']);
        error_log('Code Length: ' . $debugInfo['code_length']);
        error_log('Redirect URI: ' . $debugInfo['redirect_uri']);

        try {
            $response = $this->httpClient->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $authCode,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUrl
                ],
                'http_errors' => false,
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody(), true);
            $statusCode = $response->getStatusCode();

            // Debug der kompletten Response
            error_log('Response Status: ' . $statusCode);
            error_log('Full Response: ' . json_encode($data, JSON_PRETTY_PRINT));
            error_log('=== GOOGLE OAUTH DEBUG END ===');

            if ($statusCode !== 200) {
                $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unbekannter Fehler';
                
                return [
                    'success' => false, 
                    'error' => 'Google Fehler: ' . $errorMsg,
                    'debug' => [
                        'status_code' => $statusCode,
                        'response' => $data,
                        'request_info' => $debugInfo
                    ]
                ];
            }

            // Erfolg - Token speichern
            $this->saveToken($data);

            return [
                'success' => true,
                'message' => 'Erfolgreich mit Google Search Console verbunden!'
            ];

        } catch (\Exception $e) {
            error_log('Google OAuth Exception: ' . $e->getMessage());
            error_log('=== GOOGLE OAUTH DEBUG END ===');
            
            return [
                'success' => false,
                'error' => 'Verbindungsfehler: ' . $e->getMessage(),
                'debug' => $debugInfo
            ];
        }
    }

    /**
     * Holt alle Websites
     */
    public function getSites() {
        try {
            $token = $this->getValidToken();
            if (!$token['success']) {
                return $token;
            }

            $response = $this->httpClient->get('https://searchconsole.googleapis.com/webmasters/v3/sites', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token['access_token']
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'API Fehler'
                ];
            }

            return [
                'success' => true,
                'sites' => $data['siteEntry'] ?? []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gibt gültigen Token zurück
     */
    private function getValidToken() {
        $tokenData = $this->loadToken();
        
        if (!$tokenData || !isset($tokenData['access_token'])) {
            return ['success' => false, 'error' => 'Nicht authentifiziert'];
        }

        // Prüfe ob Token abgelaufen
        if (($tokenData['expires_at'] ?? 0) <= time() + 300) {
            return $this->refreshToken($tokenData);
        }

        return ['success' => true, 'access_token' => $tokenData['access_token']];
    }

    /**
     * Refresh Token
     */
    private function refreshToken($tokenData) {
        if (empty($tokenData['refresh_token'])) {
            return ['success' => false, 'error' => 'Token abgelaufen - neue Authentifizierung nötig'];
        }

        try {
            $response = $this->httpClient->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $tokenData['refresh_token'],
                    'grant_type' => 'refresh_token'
                ],
                'http_errors' => false
            ]);

            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200) {
                return ['success' => false, 'error' => $data['error_description'] ?? 'Refresh fehlgeschlagen'];
            }

            // Token aktualisieren
            $tokenData['access_token'] = $data['access_token'];
            $this->saveToken($tokenData);

            return ['success' => true, 'access_token' => $data['access_token']];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Prüft ob authentifiziert
     */
    public function isAuthenticated() {
        $tokenData = $this->loadToken();
        return $tokenData && isset($tokenData['access_token']);
    }

    /**
     * Prüft ob Client ID und Secret gesetzt sind
     */
    public function isConfigured() {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Gibt Konfigurations-Status zurück
     */
    public function getStatus() {
        return [
            'configured' => $this->isConfigured(),
            'authenticated' => $this->isAuthenticated(),
            'has_client_id' => !empty($this->clientId),
            'has_client_secret' => !empty($this->clientSecret)
        ];
    }

    /**
     * Löscht alle gespeicherten Daten
     */
    public function reset() {
        delete_option($this->optionName);
        $this->clientId = '';
        $this->clientSecret = '';
        return true;
    }



    /**
     * Setzt eine Domain als primäre Domain
     */
    public function setPrimaryDomain($domain) {
        $options = $this->getOptions();
        $options['primary_domain'] = sanitize_text_field($domain);
        update_option($this->optionName, $options);
        return true;
    }

    /**
     * Gibt die primäre Domain zurück
     */
    public function getPrimaryDomain() {
        $options = $this->getOptions();
        return $options['primary_domain'] ?? '';
    }

    /**
     * Gibt alle Domains mit Markierung der primären Domain zurück
     */
    public function getSitesWithPrimary() {
        $sitesResult = $this->getSites();
        
        if (!$sitesResult['success']) {
            return $sitesResult;
        }

        $primaryDomain = $this->getPrimaryDomain();
        $sites = $sitesResult['sites'];
        
        // Markiere die primäre Domain
        foreach ($sites as &$site) {
            $site['is_primary'] = ($site['siteUrl'] === $primaryDomain);
        }

        return [
            'success' => true,
            'sites' => $sites,
            'primary_domain' => $primaryDomain
        ];
    }

    /**
     * Holt Search Analytics Daten für die primäre Domain
     */
    public function getPrimaryDomainData($startDate, $endDate, $dimensions = ['query']) {
        $primaryDomain = $this->getPrimaryDomain();
        
        if (empty($primaryDomain)) {
            return [
                'success' => false,
                'error' => 'Keine primäre Domain festgelegt'
            ];
        }

        return $this->getSearchAnalytics($primaryDomain, $startDate, $endDate, $dimensions);
    }

    /**
     * Holt Search Analytics für eine spezifische Domain
     */
    public function getSearchAnalytics($siteUrl, $startDate, $endDate, $dimensions = ['query']) {
        try {
            $token = $this->getValidToken();
            if (!$token['success']) {
                return $token;
            }

            $payload = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => $dimensions,
                'rowLimit' => 1000
            ];

            $response = $this->httpClient->post(
                "https://searchconsole.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$token['access_token']}",
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload,
                    'http_errors' => false,
                    'timeout' => 30
                ]
            );

            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false, 
                    'error' => $data['error']['message'] ?? 'Unbekannter API Fehler'
                ];
            }

            return [
                'success' => true,
                'data' => $data['rows'] ?? [],
                'site' => $siteUrl
            ];

        } catch (\Exception $e) {
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }
}