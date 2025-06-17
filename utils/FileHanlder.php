<?php

namespace Utils;

class FileHanlder {

    /**
     * Uploads a file to a custom directory within the uploads folder.
     *
     * @param array  $file     The $_FILES['file'] array.
     * @param string $subdir   Subdirectory inside /wp-content/uploads/.
     * @return array|WP_Error  Returns array with 'url' and 'path' or WP_Error.
     */
    public static function upload($file, $subdir = '') 
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('upload_error', 'Ungültige Datei.');
        }

        $upload_dir = wp_upload_dir();
        $target_dir = trailingslashit($upload_dir['basedir']) . trailingslashit($subdir);
        $target_url = trailingslashit($upload_dir['baseurl']) . trailingslashit($subdir);

        // Ordner anlegen, wenn er nicht existiert
        if (!file_exists($target_dir)) {
            if (!wp_mkdir_p($target_dir)) {
                return new WP_Error('upload_error', 'Directory could not be created.');
            }
        }

        // Sicheren Dateinamen erstellen
        $filename = sanitize_file_name($file['name']);
        $target_path = $target_dir . $filename;
        $file_url = $target_url . $filename;

        // Datei verschieben
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return new WP_Error('upload_error', 'File could not be moved.');
        }

        return [
            'url'  => esc_url_raw($file_url),
            'path' => $target_path,
        ];
    }
}
