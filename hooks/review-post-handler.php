<?php

// In deinem Plugin-Hook-File, z.B. hooks/review-ajax.php

use Utils\ReviewManager;

add_action('wp_ajax_wha_toggle_review', function() {
    // Prüfe Berechtigung
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Keine Berechtigung');
        wp_die();
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error('ID fehlt');
        wp_die();
    }

    $manager = new ReviewManager();
    $review = $manager->getById($id);
    if (!$review) {
        wp_send_json_error('Bewertung nicht gefunden');
        wp_die();
    }

    $new_status = $review->is_shown ? 0 : 1;
    $ok = $manager->toggleVisibility($id, $new_status);

    if ($ok !== false) {
        wp_send_json_success(['new_status' => $new_status]);
    } else {
        wp_send_json_error('Fehler beim Aktualisieren');
    }
    wp_die();
});

add_action('wp_ajax_wha_delete_review', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Keine Berechtigung');
        wp_die();
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error('ID fehlt');
        wp_die();
    }

    $manager = new ReviewManager();
    $ok = $manager->delete($id);

    if ($ok) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Fehler beim Löschen');
    }
    wp_die();
});

function wha_create_review_callback() {
    $rating   = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $feedback = isset($_POST['feedback']) ? sanitize_text_field($_POST['feedback']) : null;
    $referral_source = isset($_POST['referral_source']) ? sanitize_text_field($_POST['referral_source']) : null;
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;

    if ($rating === null) {
        wp_send_json_error(['message' => 'Keine Bewertung angegeben']);
    }

    $manager = new \Utils\ReviewManager();

    $data = [
        'rating'   => $rating,
        'is_shown' => ($rating === 5) ? 1 : 0,
    ];

    if (!empty($feedback)) {
        $data['review_text'] = $feedback;
    }

    if (!empty($referral_source)) {
        $data['referral_source'] = $referral_source;
    }

    if (!empty($order_id)) {
        $data['order_id'] = $order_id;
    }

    try {
        $inserted = $manager->create($data);

        if ($inserted) {
            wp_send_json_success(['message' => 'Bewertung gespeichert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Speichern']);
        }
    } catch (\Throwable $e) {
        wp_send_json_error(['message' => 'Exception: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_wha_create_review', 'wha_create_review_callback');
add_action('wp_ajax_nopriv_wha_create_review', 'wha_create_review_callback');