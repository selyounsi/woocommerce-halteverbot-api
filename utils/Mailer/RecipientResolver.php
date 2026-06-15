<?php

namespace Utils\Mailer;

/**
 * Löst eine Ziel-Spezifikation in eine deduplizierte Empfängerliste auf.
 *
 * Spec-Schlüssel:
 *   - statuses[]     : WooCommerce-Bestellstatus (z.B. "wc-pending") → Empfänger aus den Bestellungen
 *   - all_customers  : bool – alle E-Mail-Adressen aus allen Bestellungen
 *   - order_ids[]    : bestimmte Bestellungen
 *   - customers[]    : bestimmte registrierte Kunden (User-IDs)
 *   - manual[]       : frei eingegebene E-Mail-Adressen
 *
 * Rückgabe: Liste von ['email' => ..., 'name' => ..., 'order_id' => int]
 */
class RecipientResolver
{
    /** Sicherheitsgrenze, damit ein Versand nicht versehentlich entgleist. */
    const CAP = 5000;

    private bool $capped = false;

    public function wasCapped(): bool
    {
        return $this->capped;
    }

    public function resolve(array $spec): array
    {
        $out = [];

        $add = function ($email, string $name = '', int $orderId = 0) use (&$out) {
            $email = sanitize_email((string) $email);
            if ($email === '' || !is_email($email)) {
                return;
            }

            $key = strtolower($email);

            if (isset($out[$key])) {
                // Vorhandenen Eintrag ggf. mit Namen / Bestell-ID anreichern.
                if ($out[$key]['order_id'] === 0 && $orderId > 0) {
                    $out[$key]['order_id'] = $orderId;
                }
                if ($out[$key]['name'] === '' && $name !== '') {
                    $out[$key]['name'] = $name;
                }
                return;
            }

            $out[$key] = ['email' => $email, 'name' => $name, 'order_id' => $orderId];
        };

        // 1. Nach Bestellstatus
        $statuses = array_filter(array_map('sanitize_text_field', (array) ($spec['statuses'] ?? [])));
        if (!empty($statuses)) {
            foreach ($this->ordersByStatus($statuses) as $r) {
                $add($r['email'], $r['name'], $r['order_id']);
            }
        }

        // 2. Alle Kunden (aus allen Bestellungen)
        if (!empty($spec['all_customers'])) {
            foreach ($this->allOrderEmails() as $r) {
                $add($r['email'], $r['name'], $r['order_id']);
            }
        }

        // 3. Bestimmte Bestellungen
        foreach ((array) ($spec['order_ids'] ?? []) as $oid) {
            $order = wc_get_order((int) $oid);
            if ($order) {
                $add(
                    $order->get_billing_email(),
                    trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                    $order->get_id()
                );
            }
        }

        // 4. Bestimmte registrierte Kunden (User-IDs)
        foreach ((array) ($spec['customers'] ?? []) as $uid) {
            $user = get_userdata((int) $uid);
            if ($user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $add($user->user_email, $name !== '' ? $name : $user->display_name);
            }
        }

        // 5. Manuelle Adressen
        foreach ((array) ($spec['manual'] ?? []) as $manual) {
            $add($manual);
        }

        return array_values($out);
    }

    /* ------------------------------------------------------------------ */

    private function ordersByStatus(array $statuses): array
    {
        $ids = wc_get_orders([
            'status'  => $statuses,
            'limit'   => self::CAP,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'ids',
        ]);

        if (is_array($ids) && count($ids) >= self::CAP) {
            $this->capped = true;
        }

        $recipients = [];
        foreach ((array) $ids as $id) {
            $order = wc_get_order($id);
            if (!$order) {
                continue;
            }
            $recipients[] = [
                'email'    => $order->get_billing_email(),
                'name'     => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'order_id' => $order->get_id(),
            ];
        }

        return $recipients;
    }

    /**
     * Effiziente DISTINCT-Abfrage aller Bestell-E-Mails (HPOS- & Legacy-tauglich),
     * ohne tausende Order-Objekte zu laden.
     */
    private function allOrderEmails(): array
    {
        global $wpdb;
        $cap         = self::CAP;
        $recipients  = [];

        if ($this->hposEnabled()) {
            $table = $wpdb->prefix . 'wc_orders';
            $rows  = $wpdb->get_results(
                "SELECT MAX(id) AS order_id, billing_email AS email
                 FROM {$table}
                 WHERE type = 'shop_order'
                   AND billing_email <> ''
                   AND status NOT IN ('trash', 'auto-draft')
                 GROUP BY billing_email
                 LIMIT {$cap}",
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                "SELECT MAX(pm.post_id) AS order_id, pm.meta_value AS email
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_billing_email'
                   AND pm.meta_value <> ''
                   AND p.post_type = 'shop_order'
                   AND p.post_status NOT IN ('trash', 'auto-draft')
                 GROUP BY pm.meta_value
                 LIMIT {$cap}",
                ARRAY_A
            );
        }

        if (is_array($rows) && count($rows) >= $cap) {
            $this->capped = true;
        }

        foreach ((array) $rows as $row) {
            $recipients[] = [
                'email'    => $row['email'],
                'name'     => '',
                'order_id' => (int) $row['order_id'],
            ];
        }

        return $recipients;
    }

    private function hposEnabled(): bool
    {
        return class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }
}
