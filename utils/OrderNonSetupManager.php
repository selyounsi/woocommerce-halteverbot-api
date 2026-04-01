<?php

namespace Utils;

class OrderNonSetupManager
{
    private int $order_id;

    // Meta-Keys
    private const META_FILES = '_order_non_setup_files';
    private const META_INFO  = '_order_non_setup_info';

    public function __construct(int $order_id)
    {
        $this->order_id = $order_id;
    }

    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    /**
     * Gibt alle Non-Setup-Daten zurück: Dateien und Grund.
     *
     * @return array{ files: string[], reason: string }
     */
    public function getNonSetupData(): array
    {
        $files = get_post_meta($this->order_id, self::META_FILES, true);
        $info  = get_post_meta($this->order_id, self::META_INFO, true);

        $files = is_array($files) ? array_values(array_filter($files)) : [];

        return [
            'files' => $files,
            'info'  => is_string($info) ? $info : '',
        ];
    }

    // -------------------------------------------------------------------------
    // REASON
    // -------------------------------------------------------------------------

    /**
     * Speichert / aktualisiert die Info zur Nicht-Aufstellung.
     */
    public function updateInfo(string $info): void
    {
        $info = sanitize_textarea_field($info);

        if (empty($info)) {
            delete_post_meta($this->order_id, self::META_INFO);
            return;
        }

        update_post_meta($this->order_id, self::META_INFO, $info);
    }

    /**
     * Löscht die gespeicherte Info.
     */
    public function deleteInfo(): void
    {
        delete_post_meta($this->order_id, self::META_INFO);
    }

    // -------------------------------------------------------------------------
    // FILES
    // -------------------------------------------------------------------------

    /**
     * Lädt eine oder mehrere Dateien hoch und speichert die URLs.
     *
     * @param array $files  $_FILES['files']
     * @return string[]|\WP_Error  Array der hochgeladenen URLs oder WP_Error
     */
    public function uploadFiles(array $files)
    {
        $normalized    = FileHanlder::normalizeFilesArray($files);
        $uploaded_urls = [];

        foreach ($normalized as $file) {
            $result = FileHanlder::upload($file, WHA_UPLOAD_PATH . "{$this->order_id}/non-setup");

            if (!is_wp_error($result)) {
                $uploaded_urls[] = $result['url'];
            }
        }

        if (empty($uploaded_urls)) {
            return new \WP_Error('upload_error', __('Keine Datei konnte hochgeladen werden.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $existing = get_post_meta($this->order_id, self::META_FILES, true);
        $existing = is_array($existing) ? $existing : [];

        update_post_meta($this->order_id, self::META_FILES, array_values(array_merge($existing, $uploaded_urls)));

        return $uploaded_urls;
    }

    /**
     * Löscht eine Datei anhand ihrer URL (Dateiname-Vergleich, robust gegen URL-Unterschiede).
     *
     * @param string $file_url
     * @return true|\WP_Error
     */
    public function deleteFile(string $file_url)
    {
        if (empty($file_url)) {
            return new \WP_Error('invalid_file', __('Datei-URL ist erforderlich.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $files = get_post_meta($this->order_id, self::META_FILES, true);

        if (empty($files) || !is_array($files)) {
            return new \WP_Error('no_files', __('Keine Dateien für diese Bestellung gefunden.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        $name_to_delete = basename(parse_url(esc_url(trim($file_url)), PHP_URL_PATH));
        $found          = false;

        foreach ($files as $key => $stored_url) {
            $stored_name = basename(parse_url(esc_url(trim($stored_url)), PHP_URL_PATH));

            if ($name_to_delete === $stored_name) {
                unset($files[$key]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new \WP_Error('file_not_found', __('Datei nicht gefunden.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        update_post_meta($this->order_id, self::META_FILES, array_values($files));

        return true;
    }
}