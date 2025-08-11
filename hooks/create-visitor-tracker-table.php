<?php

use Utils\VisitorTracker;

if (!defined('ABSPATH')) {
    exit;
}

// Tabellen nur im Admin
// add_action('admin_init', function () {
//     VisitorTracker::getInstance()->create_tables();
// });

// // Besucher nur tracken, wenn nicht eingeloggt
// add_action('init', function () {
//     if (!is_user_logged_in()) {
//         \Utils\VisitorTracker::getInstance()->track_visit();
//     }
// }, 20);