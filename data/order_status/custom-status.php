<?php

use Utils\FilenameSanitizer;
use Utils\WPCAFields;

/**
 * Attach the requested file to the email for the "bvos_custom_requested" status.
 *
 * @param array $attachments The email attachments.
 * @param string $email_id The email ID.
 * @param WC_Order $order The WooCommerce order object.
 * 
 * @return array The updated attachments list.
 */
add_filter('woocommerce_email_attachments', 'attach_requested_file_to_email', 10, 3);
function attach_requested_file_to_email($attachments, $email_id, $order) 
{
    if ($email_id === 'bvos_custom_requested' && $order instanceof WC_Order) 
    {
        // Get the absolute URL of the file from the 'requested_file' meta field
        $requested_file_url = get_post_meta($order->get_id(), '_file_upload_application', true);
        
        // Check if the URL is present and matches the site domain
        if ($requested_file_url && strpos($requested_file_url, home_url()) === 0) 
        {
            $relative_file_path = str_replace(home_url(), '', $requested_file_url);
            $file_path = ABSPATH . $relative_file_path;

            if (file_exists($file_path)) {
                // Add the file as an attachment
                $attachments[] = $file_path;
            } else {
                error_log('File could not be found: ' . $file_path);
            }
        }
    }

    return $attachments;
}

/**
 * Attach the approval file to the email for the "bvos_custom_approved" status.
 *
 * @param array $attachments The email attachments.
 * @param string $email_id The email ID.
 * @param WC_Order $order The WooCommerce order object.
 * 
 * @return array The updated attachments list.
 */
add_filter('woocommerce_email_attachments', 'attach_approval_file_to_email', 10, 3);
function attach_approval_file_to_email($attachments, $email_id, $order) 
{
    if ($email_id === 'bvos_custom_approved' && $order instanceof WC_Order) 
    {
        // Get the absolute URL of the file from the 'approval_file' meta field
        $approval_file_url = get_post_meta($order->get_id(), '_file_upload_approval', true);
        
        // Check if the URL is present and matches the site domain
        if ($approval_file_url && strpos($approval_file_url, home_url()) === 0) 
        {
            $relative_file_path = str_replace(home_url(), '', $approval_file_url);
            $file_path = ABSPATH . $relative_file_path;

            if (file_exists($file_path)) {
                // Add the file as an attachment
                $attachments[] = $file_path;
            } else {
                error_log('File could not be found: ' . $file_path);
            }
        }
    }

    return $attachments;
}

/**
 * Attach the rejection file to the email for the "bvos_custom_rejection" status.
 *
 * @param array $attachments The email attachments.
 * @param string $email_id The email ID.
 * @param WC_Order $order The WooCommerce order object.
 * 
 * @return array The updated attachments list.
 */
add_filter('woocommerce_email_attachments', 'attach_rejection_file_to_email', 10, 3);
function attach_rejection_file_to_email($attachments, $email_id, $order) 
{
    if ($email_id === 'bvos_custom_rejected' && $order instanceof WC_Order) 
    {
        // Get the absolute URL of the file from the 'rejection_file' meta field
        $rejection_file_url = get_post_meta($order->get_id(), '_file_upload_rejection', true);
        
        // Check if the URL is present and matches the site domain
        if ($rejection_file_url && strpos($rejection_file_url, home_url()) === 0) 
        {
            $relative_file_path = str_replace(home_url(), '', $rejection_file_url);
            $file_path = ABSPATH . $relative_file_path;

            if (file_exists($file_path)) {
                // Add the file as an attacNhment
                $attachments[] = $file_path;
            } else {
                error_log('File could not be found: ' . $file_path);
            }
        }
    }

    return $attachments;
}

/**
 * Attach the negativelist file and additional files to the email for the "bvos_custom_negativelist" status.
 *
 * @param array $attachments The email attachments.
 * @param string $email_id The email ID.
 * @param WC_Order $order The WooCommerce order object.
 * 
 * @return array The updated attachments list.
 */
add_filter('woocommerce_email_attachments', 'attach_negativelist_file_to_email', 10, 3);
function attach_negativelist_file_to_email($attachments, $email_id, $order) 
{
    if ($email_id === 'bvos_custom_installed' && $order instanceof WC_Order) 
    {
        $wpca = new WPCAFields($order);
        $wpcaFields = $wpca->getMetaFieldsets();
        
        $file_meta_keys = [
            '_file_upload_negativliste',
            '_file_upload_application',
            '_file_upload_approval'
        ];

        $pdf_files = [];
        $has_encrypted_pdf = false;

        foreach ($file_meta_keys as $meta_key) {
            $file_url = get_post_meta($order->get_id(), $meta_key, true);

            if ($file_url && strpos($file_url, home_url()) === 0) 
            {
                $relative_file_path = str_replace(home_url(), '', $file_url);
                $file_path = ABSPATH . $relative_file_path;

                if (file_exists($file_path)) {
                    if (is_pdf_encrypted($file_path)) {
                        $has_encrypted_pdf = true;
                    }
                    $pdf_files[] = $file_path;
                } else {
                    error_log('File could not be found: ' . $file_path);
                }
            }
        }

        if (count($pdf_files) > 1 && !$has_encrypted_pdf) 
        {
            foreach($wpcaFields as $fields) 
            {
                $fileName = FilenameSanitizer::sanitize($fields["startdate"], $fields["enddate"], $fields["address"]);
                $merged_pdf_path = ABSPATH . $fileName . '.pdf';
                merge_pdfs($pdf_files, $merged_pdf_path);
                $attachments[] = $merged_pdf_path;
            }
        } else {
            $attachments = array_merge($attachments, $pdf_files);
        }
    }

    return $attachments;
}

/**
 * Check if a PDF file is encrypted.
 *
 * @param string $file_path Path to the PDF file.
 * @return bool True if the PDF is encrypted, false otherwise.
 */
function is_pdf_encrypted($file_path) 
{
    try {
        $pdf = new setasign\Fpdi\Fpdi();
        $pdf->setSourceFile($file_path);
        return false; // PDF ist nicht verschlüsselt
    } catch (Exception $e) {
        error_log('Encrypted PDF detected: ' . $file_path);
        return true; // PDF ist verschlüsselt
    }
}

/**
 * Merge multiple PDF files into one.
 *
 * @param array $pdf_files Array of PDF file paths.
 * @param string $output_path Path to save the merged PDF.
 */
function merge_pdfs($pdf_files, $output_path) 
{
    $pdf = new setasign\Fpdi\Fpdi();

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