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
}
