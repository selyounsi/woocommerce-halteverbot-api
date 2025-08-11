<?php


add_action('wp_footer', function() {

    // Nur auf Frontend, nicht im Admin
    if (is_admin()) {
        return;
    }

    // ReviewManager laden und Bewertungen holen
    $manager = new \Utils\ReviewManager();

    // Hole nur Reviews mit is_shown=1 (angepasst an deine Methode)
    $reviews = $manager->getShownReviews(); // Beispielmethode, passe ggf. an

    if (empty($reviews)) {
        return;
    }

    // JSON-LD Schema vorbereiten
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Product", // Oder Organization / LocalBusiness, je nach Use Case
        "name" => get_bloginfo('name'),
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1),
            "reviewCount" => count($reviews),
        ],
        "review" => [],
    ];

    foreach ($reviews as $review) {
        $schema['review'][] = [
            "@type" => "Review",
            "author" => [
                "@type" => "Person",
                "name" => $review->author_name ?? 'Anonym',
            ],
            "reviewBody" => $review->review_text ?? '',
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => (int)$review->rating,
                "bestRating" => 5,
                "worstRating" => 1,
            ],
            "datePublished" => isset($review->created_at) ? date('Y-m-d', strtotime($review->created_at)) : null,
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
});

add_filter('rocket_exclude_inline_js', function($excluded_scripts) {
    // Füge deinen JSON-LD Script Block oder Keyword hinzu, damit WP Rocket ihn ignoriert
    $excluded_scripts[] = 'application/ld+json';
    return $excluded_scripts;
});