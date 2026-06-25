<?php
/**
 * Offer reminder email template.
 *
 * @package WooCommerce/Templates/Emails
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string $email_heading
 * @var string $valid_until
 * @var string $additional_content
 * @var bool $plain_text
 * @var WC_Email $email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php echo wpautop( esc_html( $additional_content ) ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
