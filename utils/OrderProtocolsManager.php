<?php
namespace Utils;

class OrderProtocolsManager
{
    private $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    // Get all licenses and files
    public function getProtocols()
    {
        $files = get_post_meta($this->order_id, '_order_file_protocols', true);
        $licenses = get_post_meta($this->order_id, '_order_license_protocols', true);

        $files = is_array($files) ? array_filter($files, fn($file) => $file !== '') : [];

        return [
            'files' => $files,
            'licenses' => is_array($licenses) ? $licenses : []
        ];
    }

    // Update licenses
    public function updateLicenses($licenses)
    {
        if (!is_array($licenses)) {
            return new \WP_Error('invalid_data', __('Invalid data format.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $sanitized_licenses = array_map(function ($license) {
            if (isset($license['license_plate'], $license['vehicle_type'], $license['color'])) {
                return [
                    'license_plate' => sanitize_text_field($license['license_plate']),
                    'vehicle_type' => sanitize_text_field($license['vehicle_type']),
                    'color' => sanitize_text_field($license['color']),
                ];
            }
            return null;
        }, $licenses);

        $sanitized_licenses = array_filter($sanitized_licenses);
        update_post_meta($this->order_id, '_order_license_protocols', $sanitized_licenses);

        return true;
    }

    // Delete a specific license
    public function deleteLicense($license_plate)
    {
        $licenses = get_post_meta($this->order_id, '_order_license_protocols', true);

        if (is_array($licenses)) {
            $licenses = array_filter($licenses, fn($license) => $license['license_plate'] !== $license_plate);
            update_post_meta($this->order_id, '_order_license_protocols', array_values($licenses));
        }

        return true;
    }

    /**
     * Upload or update a file
     */
    public function uploadFile($file)
    {
        if (empty($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('upload_error', __('Ungültige Datei.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $result = FileHanlder::upload($file, WHA_UPLOAD_PATH . "{$this->order_id}/protocols");

        if (is_wp_error($result)) {
            return $result; // Fehler weiterreichen
        }

        $file_url = $result['url'];

        $files = get_post_meta($this->order_id, '_order_file_protocols', true);
        if (!is_array($files)) {
            $files = [];
        }

        $files[] = $file_url;
        update_post_meta($this->order_id, '_order_file_protocols', $files);

        return $file_url;
    }

    /**
     * Upload or update multiple files
     */
    public function uploadFiles($files)
    {
        $normalized_files = FileHanlder::normalizeFilesArray($files);
        $uploaded_urls = [];

        foreach ($normalized_files as $file) {
            $result = FileHanlder::upload($file, WHA_UPLOAD_PATH . "{$this->order_id}/protocols");

            if (!is_wp_error($result)) {
                $uploaded_urls[] = $result['url'];
            }
        }

        if (empty($uploaded_urls)) {
            return new \WP_Error('upload_error', __('Keine Datei konnte hochgeladen werden.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $existing = get_post_meta($this->order_id, '_order_file_protocols', true);
        $existing = is_array($existing) ? $existing : [];

        $combined = array_merge($existing, $uploaded_urls);
        update_post_meta($this->order_id, '_order_file_protocols', $combined);

        return $uploaded_urls;
    }
    
    // Delete a file
    public function deleteFileX($file_url) 
    {
        if (empty($file_url)) {
            return new \WP_Error('invalid_file', __('File URL is required.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $files = get_post_meta($this->order_id, '_order_file_protocols', true);
        if (is_array($files)) {
            $files = array_filter($files, fn($file) => $file !== $file_url);
            update_post_meta($this->order_id, '_order_file_protocols', array_values($files));
        }

        return true;
    }

    // Delete a file
    public function deleteFile(string $file_url)
    {
        if (empty($file_url)) {
            return new \WP_Error('invalid_file', __('Datei-URL ist erforderlich.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $files = get_post_meta($this->order_id, '_order_file_protocols', true);

        if (empty($files) || !is_array($files)) {
            return new \WP_Error('no_files', __('No files found for this order.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        $cleaned_file_url_to_delete = esc_url(trim($file_url));
        $file_name_to_delete        = basename(parse_url($cleaned_file_url_to_delete, PHP_URL_PATH));

        $found = false;

        foreach ($files as $key => $file) {
            $cleaned_file = esc_url(trim($file));
            $file_name    = basename(parse_url($cleaned_file, PHP_URL_PATH));

            if ($file_name_to_delete === $file_name) {
                unset($files[$key]);
                $found = true;

                // Physische Datei löschen über FileHandler
                FileHanlder::delete($cleaned_file);

                break;
            }
        }

        if (!$found) {
            return new \WP_Error('file_not_found', __('File not found.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        update_post_meta($this->order_id, '_order_file_protocols', array_values($files));
        
        $this->cleanupOrphanedFiles();
        return true;
    }

    /**
     * Löscht verwaiste Dateien im Protokoll-Ordner die nicht mehr in der DB sind.
     */
    public function cleanupOrphanedFiles(): void
    {
        $upload_dir   = wp_upload_dir();
        $protocols_dir = trailingslashit($upload_dir['basedir']) . 'WHA/' . $this->order_id . '/protocols/';

        // Ordner existiert nicht → nichts zu tun
        if (!is_dir($protocols_dir)) {
            return;
        }

        // Alle Dateien die in der DB gespeichert sind
        $files_in_db = get_post_meta($this->order_id, '_order_file_protocols', true);
        $files_in_db = is_array($files_in_db) ? array_filter($files_in_db) : [];

        // Dateinamen aus den URLs extrahieren
        $db_filenames = array_map(fn($url) => basename(parse_url($url, PHP_URL_PATH)), $files_in_db);

        // Alle Dateien im Ordner durchgehen
        $files_on_disk = glob($protocols_dir . '*');

        foreach ($files_on_disk as $file_path) {
            if (!is_file($file_path)) {
                continue;
            }

            $filename = basename($file_path);

            // Datei nicht in DB → löschen
            if (!in_array($filename, $db_filenames, true)) {
                unlink($file_path);
            }
        }
    }
}
