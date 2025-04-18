<?php
namespace Utils\PDF;

use setasign\Fpdi\Fpdi;
use Exception;

class PDFHelper
{
    /**
     * Check if a PDF file is encrypted.
     *
     * @param string $file_path Path to the PDF file.
     * @return bool True if the PDF is encrypted, false otherwise.
     */
    public static function isPdfEncrypted(string $file_path): bool
    {
        try {
            $pdf = new Fpdi();
            $pdf->setSourceFile($file_path);
            return false; // PDF is not encrypted
        } catch (Exception $e) {
            error_log('Encrypted PDF detected: ' . $file_path);
            return true; // PDF is encrypted
        }
    }

    /**
     * Merge multiple PDF files into one.
     *
     * @param array $pdf_files Array of PDF file paths.
     * @param string $output_path Path to save the merged PDF.
     */
    public static function mergePdfs(array $pdf_files, string $output_path): void
    {
        $pdf = new Fpdi();

        foreach ($pdf_files as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->AddPage();
                $tplIdx = $pdf->importPage($i);
                $pdf->useTemplate($tplIdx);
            }
        }

        $pdf->Output($output_path, 'F');
    }

    /**
     * Statische Methode zum Löschen der Datei und des Post-Metas.
     *
     * @return bool True, wenn die Datei gelöscht und das Meta entfernt wurde, sonst false.
     */
    public static function deleteFileAndMeta($order, $meta_key) {
        // Auf die Instanz zugreifen
        $instance = new self(null); // Wir erstellen eine neue Instanz, aber ohne eine Order zu übergeben

        // Hole die Order-ID aus der Instanz (diese ist im Konstruktor übergeben worden)
        $post_id = $order->get_id();

        // Hole die URL der Datei aus den Post-Metadaten
        $pdf_url = get_post_meta($post_id, $meta_key, true);

        if ($pdf_url) {
            $upload_dir = wp_upload_dir();
            $pdf_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $pdf_url);

            // Überprüfen, ob die Datei existiert und sie löschen
            if (file_exists($pdf_path)) {
                $file_deleted = unlink($pdf_path);

                // Wenn die Datei gelöscht wurde, entfernen wir das Post-Meta
                if ($file_deleted) {
                    delete_post_meta($post_id, $meta_key);
                    return true;
                }
            }
        }

        return false; // Rückgabe von false, falls keine Datei gefunden oder gelöscht wurde
    }
}
