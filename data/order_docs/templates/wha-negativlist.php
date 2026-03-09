<?php

use Utils\DateUtils;
use Utils\Order\OrderBuilder;
use Utils\PDF\Invoice\CustomInvoice;
use Utils\PDF\PDFHelper;
use Utils\WPCAFields;

// ─── Initialisierung ────────────────────────────────────────────────────────

$wpo   = new CustomInvoice();
$order = ($this instanceof \Utils\PDF\Generator)
    ? new OrderBuilder($this->data)
    : new OrderBuilder(['id' => $order->get_id()]);

$wc_order    = $order->getOrder();
$order_id    = $wc_order->get_id();
$wpca        = new WPCAFields($wc_order);
$wpcaFields  = $wpca->getMetaFieldsets();

// ─── Installer-Metadaten einmalig auflösen (außerhalb der Schleife) ──────────

$installer_name = '';
$installer_date = '';

if ($wc_order && $order_id) {
    $installer_name = $wc_order->get_meta('installer_name');
    if (empty($installer_name)) {
        $installer_name = $order->getMetaValue('_file_upload_negativliste_installer');
        if (!empty($installer_name)) {
            $wc_order->update_meta_data('installer_name', $installer_name);
        }
    }

    $installer_date = $wc_order->get_meta('installer_date');
    if (empty($installer_date)) {
        $installer_date = $order->getMetaValue('_file_upload_negativliste_date');
        if (!empty($installer_date)) {
            $wc_order->update_meta_data('installer_date', $installer_date);
        }
    }

    $wc_order->save();
    PDFHelper::deleteFileAndMeta($wc_order, '_file_upload_negativliste');
}

// ─── Verkehrsmaßnahmen auswerten ─────────────────────────────────────────────

$checked = [
    'Z286'     => '',
    'Z283'     => '',
    'Z286_Z283' => '',
    'Z283_Z283' => '',
];

$measures = get_post_meta($order_id, '_traffic_measures', true);

if (is_array($measures)) {
    $map = [
        'Z286'      => ['286-50', '286'],
        'Z283'      => ['283-50', '283'],
        'Z286_Z283' => ['286-50-ggue-283-50', '286-ggue-283'],
        'Z283_Z283' => ['283-10-ggue-283-20', '283-ggue-283'],
    ];

    foreach ($measures as $measure) {
        foreach ($map as $key => $values) {
            if (in_array($measure['main'] ?? '', $values, true)) {
                $checked[$key] = 'checked';
            }
        }
    }
}

// ─── Kennzeichen-Protokolle ───────────────────────────────────────────────────

$license_protocols = get_post_meta($order_id, '_order_license_protocols', true);
if (!is_array($license_protocols)) {
    $license_protocols = [];
}

$maxRows       = 10;
$protocolCount = count($license_protocols);
$rowCount      = max($maxRows, $protocolCount);

// ─── Hilfsfunktion: Datumsbereich formatieren ────────────────────────────────

function formatDateRange(string $startDate, string $startTime, string $endDate, string $endTime): string
{
    $start = DateUtils::formatToGermanDate($startDate);
    $end   = DateUtils::formatToGermanDate($endDate);

    return ($startDate === $endDate)
        ? "{$start} {$startTime} – {$endTime}"
        : "{$start} {$startTime} – {$end} {$endTime}";
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        <?php require WHA_PLUGIN_PATH . '/data/order_docs/templates/assets/style.css'; ?>

        /* Lokale Ergänzungen */
        .neg-table       { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .neg-table td    { padding: 5px; border: none; }
        .neg-table td.label { font-weight: bold; white-space: nowrap; }
        .sign-table      { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .sign-table td   { padding: 5px; border: none; }
        .sign-table input[type="checkbox"] { position: relative; top: 6px; margin: 0; }
        .license-table   { width: 100%; border-collapse: collapse; }
        .license-table th,
        .license-table td { border: 1px solid #000; padding: 5px; }
        .license-table th { background: #f2f2f2; }
    </style>
</head>
<body>

    <!-- LOGO -->
    <table class="head container">
        <tr>
            <td class="header"><?php echo $wpo->displayHeaderLogo(); ?></td>
        </tr>
    </table>
    <br>

    <h1 class="document-type-label">
        Negativliste für den Auftrag <?php echo esc_html($wc_order->get_order_number()); ?>
    </h1>

    <!-- ── Auftragsdaten je Fieldset ─────────────────────────────────────── -->
    <?php foreach ($wpcaFields as $fields) : ?>
        <table class="neg-table">
            <tbody>
                <tr>
                    <td class="label"><?= esc_html__('Ort der Aufstellung') ?>:</td>
                    <td><?= esc_html("{$fields['address']}, {$fields['postalcode']} {$fields['place']}") ?></td>
                </tr>
                <tr>
                    <td class="label"><?= esc_html__('Länge HVZ') ?>:</td>
                    <td><?= esc_html($fields['distance_unit'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="label"><?= esc_html__('Kunde') ?>:</td>
                    <td><?= esc_html("{$fields['client_fname']} {$fields['client_lname']}") ?></td>
                </tr>
                <tr>
                    <td class="label"><?= esc_html__('Ausstellungsgrund') ?>:</td>
                    <td><?= esc_html($fields['reason'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="label"><?= esc_html__('Aufstellungsdatum') ?>:</td>
                    <td><?= esc_html(formatDateRange(
                            $fields['startdate'] ?? '',
                            $fields['starttime'] ?? '',
                            $fields['enddate']   ?? '',
                            $fields['endtime']   ?? ''
                        )) ?>
                    </td>
                </tr>

                <?php if (!empty($installer_name)) : ?>
                    <tr>
                        <td class="label"><?= esc_html__('Aufsteller') ?>:</td>
                        <td><?= esc_html($installer_name) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($installer_date)) : ?>
                    <tr>
                        <td class="label"><?= esc_html__('Aufgestellt am') ?>:</td>
                        <td><?= esc_html(DateUtils::formatToGermanDate($installer_date)) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <!-- ── Verkehrszeichen-Checkboxen ────────────────────────────────────── -->
    <table class="sign-table">
        <tbody>
            <tr>
                <td>
                    <input type="checkbox" <?= $checked['Z286'] ?>>
                    Z 286 StVO
                </td>
                <td>
                    <input type="checkbox" <?= $checked['Z283'] ?>>
                    Z 283 StVO
                </td>
            </tr>
            <tr>
                <td>
                    <input type="checkbox" <?= $checked['Z286_Z283'] ?>>
                    Z 286 StVO und gegenüber Z 283 StVO
                </td>
                <td>
                    <input type="checkbox" <?= $checked['Z283_Z283'] ?>>
                    Z 283 StVO und gegenüber Z 283 StVO
                </td>
            </tr>
        </tbody>
    </table>

    <!-- ── Kennzeichen-Protokoll ──────────────────────────────────────────── -->
    <table class="license-table">
        <thead>
            <tr>
                <th>Kennzeichen</th>
                <th>Fahrzeugtyp</th>
                <th>Farbe</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i < $rowCount; $i++) :
                $row   = $license_protocols[$i] ?? [];
                $plate = esc_html($row['license_plate'] ?? '');
                $type  = esc_html($row['vehicle_type']  ?? '');
                $color = esc_html($row['color']         ?? '');
            ?>
                <tr>
                    <td><?= $plate !== '' ? $plate : '&nbsp;' ?></td>
                    <td><?= $type  !== '' ? $type  : '&nbsp;' ?></td>
                    <td><?= $color !== '' ? $color : '&nbsp;' ?></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

</body>
</html>