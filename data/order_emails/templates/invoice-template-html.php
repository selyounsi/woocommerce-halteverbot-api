<?php
/**
 * Angebots-E-Mail-Template
 *
 * Diese Vorlage wird verwendet, um die Angebots-E-Mail zu senden.
 *
 * @package WooCommerce/Templates/Emails
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Verhindert direkten Zugriff
}

/**
 * @var string $email_heading
 * @var string $valid_until
 * @var string $offer_details
 * @var bool $plain_text
 * @var WC_Email $email
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $email_heading ?? 'Angebots-E-Mail' ); ?></title>
    <style type="text/css">
        /* E-Mail-spezifisches Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
            margin: 0;
        }
        table {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .email-header {
            background-color: #0073aa;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
        }
        .email-content {
            font-size: 16px;
            color: #333;
            padding: 20px 0 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #fff9f9;
            margin-top: 20px;
            background: #0073aa;
            border-radius: 0 0 5px 5px;
        }
        a {
            color: #0073aa;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <td class="email-header">
                <h1><?php echo esc_html($email_heading); ?></h1>
            </td>
        </tr>
        <tr>
            <td class="email-content">
                <p><?php echo wpautop( esc_html( $additional_content ) ); ?></p>
            </td>
        </tr>
        <tr>
            <td class="footer">
                <p><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
            </td>
        </tr>
    </table>
</body>
</html>
