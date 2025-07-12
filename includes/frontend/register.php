<?php

// Your custom pages
$custom_pages = [
    'reviews' => [
        'template' => 'reviews.php',
        'meta' => [
            'title' => 'Bewertung - Halteverbotsservice Berlin',
            'description' => 'Bewerten Sie unseren Service einfach online.',
        ],
    ],
    'order-details' => [
        'template' => 'order-details.php',
        'meta' => [
            'title' => 'Bestelldetails - Halteverbotsservice Berlin',
            'description' => 'Details zu Ihrer Halteverbotsbestellung.',
        ],
    ],
];

// Make access global (optional)
$GLOBALS['custom_pages'] = $custom_pages;

// Dynamically integrate templates
add_action('template_redirect', function () use ($custom_pages) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    if (array_key_exists($uri, $custom_pages)) {

        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);

        // Remember meta information for later
        $GLOBALS['custom_page_meta'] = $custom_pages[$uri]['meta'];

         // Include template
        include plugin_dir_path(__FILE__) . 'pages/' . $custom_pages[$uri]['template'];
        exit;
    }
});