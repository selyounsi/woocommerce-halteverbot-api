<?php

use Utils\FilenameSanitizer;
use Utils\OrderProtocolsManager;
use Utils\PDF\PDFHelper;
use Utils\WPCAFields;
use Utils\PDF\Generator;

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
    if (str_contains($email_id, "rejected") && $order instanceof WC_Order) 
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
        $image_files = []; // Für die Protocol-Bilder
        $has_encrypted_pdf = false;

        // Bestehende PDF-Dateien verarbeiten
        foreach ($file_meta_keys as $meta_key) {
            $file_url = get_post_meta($order->get_id(), $meta_key, true);

            if ($file_url && strpos($file_url, home_url()) === 0) 
            {
                $relative_file_path = str_replace(home_url(), '', $file_url);
                $file_path = ABSPATH . $relative_file_path;

                if (file_exists($file_path)) {
                    if (PDFHelper::isPdfEncrypted($file_path)) {
                        $has_encrypted_pdf = true;
                    }
                    $pdf_files[] = $file_path;
                } else {
                    error_log('File could not be found: ' . $file_path);
                }
            }
        }

        // Protocol-Bilder über OrderProtocolsManager hinzufügen
        $protocolsManager = new OrderProtocolsManager($order->get_id());
        $protocols = $protocolsManager->getProtocols();
        
        if (is_array($protocols['files']) && !empty($protocols['files'])) {
            foreach ($protocols['files'] as $file_url) {
                if ($file_url && strpos($file_url, home_url()) === 0) {
                    $relative_file_path = str_replace(home_url(), '', $file_url);
                    $file_path = ABSPATH . $relative_file_path;

                    if (file_exists($file_path)) {
                        // Prüfen ob es sich um ein Bild handelt
                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $image_files[] = $file_path;
                        } else {
                            // Falls es keine Bilder sind, zu den PDFs hinzufügen
                            $pdf_files[] = $file_path;
                        }
                    } else {
                        error_log('Protocol file could not be found: ' . $file_path);
                    }
                }
            }
        }

        if(!get_post_meta($order->get_id(), '_file_upload_negativliste', true)) {
            // Generate and add the negativelist PDF as a Base64 string
            $doc = new Generator($order);
            $doc->generatePDF("negativlist"); 
            $base64 = $doc->getBase64();

            // Save the Base64 content as a temporary PDF file
            $negativlist_temp_file = tempnam(sys_get_temp_dir(), 'negativlist_') . '.pdf';
            file_put_contents($negativlist_temp_file, base64_decode($base64));
            $pdf_files[] = $negativlist_temp_file;
        }

        // Bilder zu den Attachments hinzufügen
        $attachments = array_merge($attachments, $image_files);

        // PDFs verarbeiten (Merge oder einzeln anhängen)
        if (count($pdf_files) > 1 && !$has_encrypted_pdf) 
        {
            foreach($wpcaFields as $fields) 
            {
                $fileName = FilenameSanitizer::sanitize($fields["startdate"], $fields["enddate"], $fields["address"]);
                $merged_pdf_path = ABSPATH . $fileName . '.pdf';
                PdfHelper::mergePdfs($pdf_files, $merged_pdf_path);
                $attachments[] = $merged_pdf_path;
            }
        } else {
            $attachments = array_merge($attachments, $pdf_files);
        }
    }

    return $attachments;
}