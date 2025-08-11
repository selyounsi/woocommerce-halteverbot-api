<?php
if (!defined('ABSPATH')) {
    exit;
}

function wha_create_reviews_table() {
    $manager = new \Utils\ReviewManager();
    // $manager->dropTable();
    $manager->createTable();
    $manager->updateTable();
}

add_action('admin_init', 'wha_create_reviews_table');
