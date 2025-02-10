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

    // Upload or update a file
    public function uploadFile($file)
    {
        // Load the required file for wp_handle_upload function
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error'], ['status' => 500]);
        }

        $file_url = esc_url($upload['url']);
        $files = get_post_meta($this->order_id, '_order_file_protocols', true);
        if (!is_array($files)) {
            $files = [];
        }
        $files[] = $file_url;
        update_post_meta($this->order_id, '_order_file_protocols', $files);

        return $file_url;
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
    public function deleteFile($file_url) 
    {
        if (empty($file_url)) {
            return new \WP_Error('invalid_file', __('File URL is required.', WHA_TRANSLATION_KEY), ['status' => 400]);
        }

        $files = get_post_meta($this->order_id, '_order_file_protocols', true);

        // Überprüfe, ob keine Dateien vorhanden sind
        if (empty($files)) {
            return new WP_Error('no_files', __('No files found for this order.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        // Bereinige und extrahiere den Dateinamen aus der URL, die gelöscht werden soll
        $cleaned_file_url_to_delete = esc_url(trim($file_url));
        $file_name_to_delete = basename(parse_url($cleaned_file_url_to_delete, PHP_URL_PATH));

        $found = false;

        // Durchlaufe die Liste der Dateien und entferne die Datei, wenn der Name übereinstimmt
        foreach ($files as $key => $file) {
            // Bereinige und extrahiere den Dateinamen aus der Datei im Array
            $cleaned_file = esc_url(trim($file));
            $file_name = basename(parse_url($cleaned_file, PHP_URL_PATH));

            // Vergleiche nur die Dateinamen
            if ($file_name_to_delete == $file_name) {
                unset($files[$key]); // Entferne das Element aus dem Array
                $found = true;
                break; // Beende die Schleife, sobald die Datei entfernt wurde
            }
        }

        // Falls die Datei nicht gefunden wurde, gib einen Fehler zurück
        if (!$found) {
            return new WP_Error('file_not_found', __('File not found.', WHA_TRANSLATION_KEY), ['status' => 404]);
        }

        // Update die Post-Metadaten, nachdem das Array aktualisiert wurde
        update_post_meta($this->order_id, '_order_file_protocols', array_values($files));

        return true;
    }
}
