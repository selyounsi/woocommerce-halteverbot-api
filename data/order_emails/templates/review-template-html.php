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
 * @var string $review_details
 * @var bool $plain_text
 * @var WC_Email $email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo wpautop( wp_kses_post( $additional_content ) ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
