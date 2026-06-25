<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/email-templates', [
        'methods' => 'GET',
        'callback' => 'wha_get_email_templates',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    register_rest_route('wc/v3', '/email-templates/(?P<id>offer|invoice|offer_reminder|invoice_reminder)', [
        'methods' => 'POST',
        'callback' => 'wha_update_email_template',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

/**
 * Maps the public template id to the WooCommerce email settings option name.
 */
function wha_email_template_option_map()
{
    return [
        'offer'            => 'woocommerce_offer_email_settings',
        'invoice'          => 'woocommerce_invoice_email_settings',
        'offer_reminder'   => 'woocommerce_offer_reminder_email_settings',
        'invoice_reminder' => 'woocommerce_invoice_reminder_email_settings',
    ];
}

/**
 * Maps the public template id to the registered WC_Email class key.
 */
function wha_email_template_class_map()
{
    return [
        'offer'            => 'WC_Email_Offer',
        'invoice'          => 'WC_Email_Invoice',
        'offer_reminder'   => 'WC_Email_Offer_Reminder',
        'invoice_reminder' => 'WC_Email_Invoice_Reminder',
    ];
}

/**
 * Returns the four email templates with their effective values
 * (stored setting or the class default fallback).
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function wha_get_email_templates(WP_REST_Request $request)
{
    $emails = WC()->mailer()->get_emails();
    $class_map = wha_email_template_class_map();
    $option_map = wha_email_template_option_map();

    $out = [];

    foreach ($class_map as $id => $class) {
        $email = $emails[$class] ?? null;

        if ($email) {
            $out[$id] = [
                'subject'            => $email->subject,
                'heading'            => $email->heading,
                'additional_content' => $email->additional_content,
                'bcc'                => isset($email->bcc) ? $email->bcc : '',
            ];
        } else {
            $settings = maybe_unserialize(get_option($option_map[$id]));
            $settings = is_array($settings) ? $settings : [];
            $out[$id] = [
                'subject'            => $settings['subject'] ?? '',
                'heading'            => $settings['heading'] ?? '',
                'additional_content' => $settings['additional_content'] ?? '',
                'bcc'                => $settings['bcc'] ?? '',
            ];
        }
    }

    return rest_ensure_response($out);
}

/**
 * Updates subject / heading / additional_content / bcc for one template,
 * preserving all other keys of the WooCommerce email settings option.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function wha_update_email_template(WP_REST_Request $request)
{
    $id = $request->get_param('id');
    $option_map = wha_email_template_option_map();

    if (! isset($option_map[$id])) {
        return new WP_Error('invalid_id', 'Unknown email template id.', ['status' => 400]);
    }

    $option_name = $option_map[$id];
    $settings = maybe_unserialize(get_option($option_name));
    $settings = is_array($settings) ? $settings : [];

    $body = $request->get_json_params();
    $body = is_array($body) ? $body : [];

    if (array_key_exists('subject', $body)) {
        $settings['subject'] = sanitize_text_field($body['subject']);
    }
    if (array_key_exists('heading', $body)) {
        $settings['heading'] = sanitize_text_field($body['heading']);
    }
    if (array_key_exists('additional_content', $body)) {
        $settings['additional_content'] = sanitize_textarea_field($body['additional_content']);
    }
    if (array_key_exists('bcc', $body)) {
        $settings['bcc'] = sanitize_text_field($body['bcc']);
    }

    update_option($option_name, $settings);

    return rest_ensure_response([
        'success'  => true,
        'id'       => $id,
        'template' => [
            'subject'            => $settings['subject'] ?? '',
            'heading'            => $settings['heading'] ?? '',
            'additional_content' => $settings['additional_content'] ?? '',
            'bcc'                => $settings['bcc'] ?? '',
        ],
    ]);
}
