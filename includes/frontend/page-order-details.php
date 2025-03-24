<?php
/* Template Name: Order Details Page */

use Utils\Order\OrderBuilder;
use Utils\PDF\Generator;
use Utils\PDF\Invoice\Preview;
use Utils\PDF\InvoicePreview;

get_header(); ?>

<div class="content content_top_margin">
    <div class="content_inner">
        <div class="container">
            <div class="container_inner">
                <h1>Order Details</h1>
                <p>Here you can find the details of your order.</p>
            </div>
        </div>
    </div>


    <?php

        $data = [
            "payment_method" => "bacs",
            "payment_method_title" => "Direkte Banküberweisung",
            "status" => "on-hold",
            "billing" => [
                "country" => "DE",
                "company" => "Valov Bau GmbH",
                "first_name" => "",
                "last_name" => "",
                "address_1" => "Mierendorffplatz 5",
                "city" => "Berlin",
                "postcode" => "10589",
                "email" => "s.elyounsi@wwwe.de",
                "phone" => ""
            ],
            "document_note" => "Hallo",
            "customer_note" => "Test",
            "line_items" => [
                [
                    "product_id" => 695,
                    "quantity" => 1,
                    "subtotal" => "100.84",
                    "subtotal_tax" => "19.16",
                    "total" => "100.84",
                    "total_tax" => "19.16",
                    "meta_data" => [
                        ["key" => "Startdatum", "value" => "2025-01-15"],
                        ["key" => "Enddatum", "value" => "2025-01-15"],
                        ["key" => "Anfangszeit", "value" => "07:00"],
                        ["key" => "Endzeit", "value" => "17:00"],
                        ["key" => "Strecke", "value" => "15"],
                        ["key" => "Grund", "value" => "Bauarbeiten"],
                        ["key" => "Postleitzahl", "value" => "12101"],
                        ["key" => "Ort", "value" => "Berlin"],
                        ["key" => "Straße + Hausnummer", "value" => "Tempelhofer Damm 2"],
                        ["key" => "Inklusive behördlicher Genehmigung", "value" => false],
                        ["key" => "Gegenüberliegende Straßenseite sperren", "value" => false],
                        ["key" => "Anzahl der Tage", "value" => 1]
                    ]
                ]
            ],
            "meta_data" => [
                [
                    "key" => "wpo_wcpdf_invoice_positions",
                    "value" => [
                        [
                            "name" => "Inkl. Genehmigung",
                            "readonly" => true,
                            "edit" => false,
                            "description" => "inkl. Behördliche Genehmigung",
                            "days" => 9,
                            "quantity" => 1,
                            "total" => "30",
                            "netto" => "25.21"
                        ],
                        [
                            "id" => 1,
                            "name" => "Schilder",
                            "readonly" => 1,
                            "description" => "Aufstellung und Abholung der Schilder sowie Protokollierung",
                            "days" => 1,
                            "quantity" => 2,
                            "total" => "90.00",
                            "netto" => "75.63",
                            "default" => 0,
                            "created_at" => null,
                            "updated_at" => null
                        ]
                    ]
                ],
                [
                    "key" => "installer_name",
                    "value" => "Sami"
                ],
                [
                    "key" => "_disable_new_order_notification",
                    "value" => true
                ],
                [
                    "key" => "document_number",
                    "value" => "000028"
                ],
                [
                    "key" => "document_created",
                    "value" => "2025-01-18"
                ],
                [
                    "key" => "order_time_type",
                    "value" => "range"
                ],
                [
                    "key" => "_traffic_measures",
                    "value" => [
                        [
                            "main" => "209-10",
                            "count" => 1,
                            "sub_measures" => [
                                [
                                    "measure" => "1053-30",
                                    "count" => 2,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "total" => "120",
            "discount_tax" => "0",
            "discount_total" => "0"
        ];


        $dataX = [
            "id" => 6077,
            "meta_data" => [
                [
                    "key" => "installer_name",
                    "value" => "Sami"
                ],
                [
                    "key" => "installer_date",
                    "value" => "2025-01-11 12:00"
                ]
            ]
        ];


        $order = wc_get_order(6077);

        $invoice = new Generator($data);
        // $invoice = new Generator($order);

        

        $invoice->generatePDF("negativlist");
        $blob = $invoice->getBase64();
    ?>

    <?php if (isset($blob)): ?>
        <iframe src="data:application/pdf;base64,<?php echo $blob; ?>" width="100%" height="900px"></iframe>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
