<?php

namespace Utils\Tracker\Google;

use GuzzleHttp\Client;

class GoogleSearchConsole {
    private static $instance = null;
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
     * Gibt immer die gleiche Instanz zurück
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Verhindere Klonen
    private function __clone() {}

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
    public function getPrimaryDomainData($payload) {
        $primaryDomain = $this->getPrimaryDomain();
        
        if (empty($primaryDomain)) {
            return [
                'success' => false,
                'error' => 'Keine primäre Domain festgelegt'
            ];
        }

        return $this->getSearchAnalyticsData($payload);
    }

    /**
     * Holt Search Analytics Daten mit komplettem Payload
     */
    public function getSearchAnalyticsData($payload) {
        try {
            $primaryDomain = $this->getPrimaryDomain();
            
            if (empty($primaryDomain)) {
                return [
                    'success' => false,
                    'error' => 'Keine primäre Domain festgelegt'
                ];
            }

            $token = $this->getValidToken();
            if (!$token['success']) {
                return $token;
            }

            $response = $this->httpClient->post(
                "https://searchconsole.googleapis.com/webmasters/v3/sites/" . urlencode($primaryDomain) . "/searchAnalytics/query",
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
                'data' => $data['rows'] ?? []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }






























    /**
     * Verbesserte Authentifizierungs-Methode mit automatischer Erneuerung
     */
    public function authenticate($code) {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        $redirectUri = $this->getCurrentUrl();

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 30,
            'body' => [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('[Halteverbot GSC] Auth Fehler: ' . $response->get_error_message());
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode !== 200 || isset($data['error'])) {
            $error = $data['error_description'] ?? $data['error'] ?? 'Unbekannter Fehler';
            error_log('[Halteverbot GSC] Auth API Fehler: ' . $error);
            return ['success' => false, 'error' => $error];
        }

        // ✅ Token mit zusätzlichen Metadaten speichern
        $tokenData = [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
            'scope' => $data['scope'] ?? '',
            'token_type' => $data['token_type'] ?? 'Bearer',
            'created' => time(),
            'last_used' => time(),
            'refresh_count' => 0
        ];

        // Refresh Token nur speichern wenn vorhanden
        if (!empty($data['refresh_token'])) {
            $tokenData['refresh_token'] = $data['refresh_token'];
        } else {
            // Falls kein Refresh Token zurückkommt, vorhandenen behalten
            $existing = $this->loadToken();
            if (!empty($existing['refresh_token'])) {
                $tokenData['refresh_token'] = $existing['refresh_token'];
            }
        }

        $this->saveToken($tokenData);

        error_log('[Halteverbot GSC] Auth erfolgreich - Token gespeichert');
        return ['success' => true, 'message' => 'Erfolgreich mit Google verbunden!'];
    }

    /**
     * Verbesserte Token-Refresh Methode
     */
    private function refreshToken($tokenData) {
        if (empty($tokenData['refresh_token'])) {
            error_log('[Halteverbot GSC] Kein Refresh Token verfügbar');
            return [
                'success' => false,
                'error' => 'Kein Refresh Token gespeichert – bitte Verbindung neu herstellen.'
            ];
        }

        // Prüfe ob zu oft versucht wurde zu refreshen
        if (($tokenData['refresh_count'] ?? 0) > 3) {
            error_log('[Halteverbot GSC] Zu viele Refresh-Versuche');
            return [
                'success' => false,
                'error' => 'Zu viele fehlgeschlagene Refresh-Versuche – bitte neu authentifizieren.'
            ];
        }

        try {
            $response = $this->httpClient->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $tokenData['refresh_token'],
                    'grant_type' => 'refresh_token'
                ],
                'http_errors' => false,
                'timeout' => 20
            ]);

            $data = json_decode($response->getBody(), true);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200 || !isset($data['access_token'])) {
                $error = $data['error_description'] ?? $data['error'] ?? 'Unbekannter Fehler';
                error_log("[Halteverbot GSC] Refresh fehlgeschlagen ($statusCode): " . $error);
                
                // Erhöhe Refresh-Counter
                $tokenData['refresh_count'] = ($tokenData['refresh_count'] ?? 0) + 1;
                $this->saveToken($tokenData);
                
                return ['success' => false, 'error' => $error];
            }

            // ✅ Token erfolgreich aktualisiert
            $tokenData['access_token'] = $data['access_token'];
            $tokenData['expires_in'] = $data['expires_in'] ?? 3600;
            $tokenData['expires_at'] = time() + $tokenData['expires_in'];
            $tokenData['last_refresh'] = time();
            $tokenData['refresh_count'] = 0; // Reset counter bei Erfolg
            $tokenData['last_used'] = time();

            $this->saveToken($tokenData);
            error_log('[Halteverbot GSC] Token erfolgreich aktualisiert');

            return [
                'success' => true,
                'access_token' => $tokenData['access_token']
            ];

        } catch (\Exception $e) {
            error_log('[Halteverbot GSC] Exception beim Refresh: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Verbindungsfehler: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verbesserte Token-Validierung mit Proactive Refresh
     */
    private function getValidToken() {
        $tokenData = $this->loadToken();
            
        if (!$tokenData || !isset($tokenData['access_token'])) {
            return ['success' => false, 'error' => 'Nicht authentifiziert'];
        }

        // Prüfe ob Token bald abläuft (10 Minuten Puffer)
        $expiresAt = $tokenData['expires_at'] ?? ($tokenData['created'] + ($tokenData['expires_in'] ?? 3600));
        
        if ($expiresAt <= time() + 600) {
            error_log('[Halteverbot GSC] Token läuft bald ab, versuche Refresh');
            return $this->refreshToken($tokenData);
        }

        // Aktualisiere last_used Zeitstempel
        $tokenData['last_used'] = time();
        $this->saveToken($tokenData);

        return ['success' => true, 'access_token' => $tokenData['access_token']];
    }

    /**
     * Verbesserte Token-Speicherung
     */
    private function saveToken($tokenData) {
        $options = $this->getOptions();
        
        // Berechne expires_at falls nicht vorhanden
        if (!isset($tokenData['expires_at']) && isset($tokenData['created']) && isset($tokenData['expires_in'])) {
            $tokenData['expires_at'] = $tokenData['created'] + $tokenData['expires_in'];
        }
        
        $options['token_data'] = $tokenData;
        $options['last_updated'] = time();
        
        update_option($this->optionName, $options);
        return true;
    }

    /**
     * Prüft Token-Status detailliert
     */
    public function getTokenStatus() {
        $tokenData = $this->loadToken();
        
        if (!$tokenData) {
            return ['valid' => false, 'reason' => 'Kein Token gespeichert'];
        }
        
        $expiresAt = $tokenData['expires_at'] ?? ($tokenData['created'] + ($tokenData['expires_in'] ?? 3600));
        $timeToExpiry = $expiresAt - time();
        
        return [
            'valid' => $timeToExpiry > 600, // 10 Minuten Puffer
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'time_to_expiry' => $this->formatSeconds($timeToExpiry),
            'has_refresh_token' => !empty($tokenData['refresh_token']),
            'refresh_count' => $tokenData['refresh_count'] ?? 0,
            'last_used' => isset($tokenData['last_used']) ? date('Y-m-d H:i:s', $tokenData['last_used']) : 'Nie'
        ];
    }
    
    private function formatSeconds($seconds) {
        if ($seconds < 60) return "$seconds Sekunden";
        if ($seconds < 3600) return round($seconds/60) . " Minuten";
        return round($seconds/3600, 1) . " Stunden";
    }
}