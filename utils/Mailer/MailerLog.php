<?php

namespace Utils\Mailer;

/**
 * Verwaltet die beiden Mailer-Tabellen:
 *   - wha_mailer_campaigns : eine Zeile pro Versand (Betreff, Inhalt, Ziel, Status)
 *   - wha_mailer_log       : eine Zeile pro Empfänger (Status, Fehler, Zeitpunkt)
 */
class MailerLog
{
    private $wpdb;
    private string $campaignsTable;
    private string $logTable;

    private array $campaignColumns = [
        'id'           => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
        'subject'      => 'VARCHAR(255) NOT NULL DEFAULT ""',
        'body'         => 'LONGTEXT NULL',
        'attachments'  => 'TEXT NULL',
        'targeting'    => 'TEXT NULL',
        'sandbox'      => 'TINYINT(1) NOT NULL DEFAULT 0',
        'test_address' => 'VARCHAR(190) DEFAULT NULL',
        'total'        => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'sent'         => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'failed'       => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'status'       => 'VARCHAR(20) NOT NULL DEFAULT "queued"',
        'created_by'   => 'BIGINT(20) UNSIGNED DEFAULT NULL',
        'created_at'   => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];

    private array $logColumns = [
        'id'           => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
        'campaign_id'  => 'BIGINT(20) UNSIGNED NOT NULL',
        'email'        => 'VARCHAR(190) NOT NULL',
        'name'         => 'VARCHAR(190) DEFAULT NULL',
        'order_id'     => 'BIGINT(20) UNSIGNED NOT NULL DEFAULT 0',
        'status'       => 'VARCHAR(20) NOT NULL DEFAULT "queued"',
        'error'        => 'TEXT NULL',
        'processed_at' => 'DATETIME NULL DEFAULT NULL',
        'created_at'   => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb           = $wpdb;
        $this->campaignsTable = $wpdb->prefix . 'wha_mailer_campaigns';
        $this->logTable       = $wpdb->prefix . 'wha_mailer_log';
    }

    public function getCampaignsTable(): string { return $this->campaignsTable; }
    public function getLogTable(): string { return $this->logTable; }

    /* ---------------------------------------------------------------------
     * Schema
     * ------------------------------------------------------------------- */

    public function createTables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $this->wpdb->get_charset_collate();

        $this->createTable(
            $this->campaignsTable,
            $this->campaignColumns,
            ['PRIMARY KEY (id)'],
            $charset
        );

        $this->createTable(
            $this->logTable,
            $this->logColumns,
            ['PRIMARY KEY (id)', 'KEY campaign_id (campaign_id)', 'KEY status (status)'],
            $charset
        );
    }

    private function createTable(string $table, array $columns, array $keys, string $charset): void
    {
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            return;
        }

        $defs = [];
        foreach ($columns as $name => $definition) {
            $defs[] = "$name $definition";
        }
        foreach ($keys as $key) {
            $defs[] = $key;
        }

        $sql = "CREATE TABLE {$table} (\n  " . implode(",\n  ", $defs) . "\n) ENGINE=InnoDB {$charset};";
        dbDelta($sql);
    }

    public function updateTables(): void
    {
        $this->updateTable($this->campaignsTable, $this->campaignColumns);
        $this->updateTable($this->logTable, $this->logColumns);
    }

    private function updateTable(string $table, array $columns): void
    {
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return;
        }

        $existing = $this->wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        foreach ($columns as $col => $definition) {
            if (!in_array($col, $existing, true)) {
                $this->wpdb->query("ALTER TABLE {$table} ADD COLUMN $col $definition");
            }
        }
    }

    /* ---------------------------------------------------------------------
     * Kampagnen
     * ------------------------------------------------------------------- */

    public function createCampaign(array $data): int
    {
        $this->wpdb->insert(
            $this->campaignsTable,
            [
                'subject'      => (string) ($data['subject'] ?? ''),
                'body'         => (string) ($data['body'] ?? ''),
                'attachments'  => wp_json_encode(array_values($data['attachments'] ?? [])),
                'targeting'    => wp_json_encode($data['targeting'] ?? []),
                'sandbox'      => !empty($data['sandbox']) ? 1 : 0,
                'test_address' => $data['test_address'] ?? null,
                'total'        => (int) ($data['total'] ?? 0),
                'status'       => (string) ($data['status'] ?? 'queued'),
                'created_by'   => get_current_user_id(),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d']
        );

        return (int) $this->wpdb->insert_id;
    }

    public function getCampaign(int $id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->campaignsTable} WHERE id = %d", $id)
        );
    }

    public function getCampaigns(int $limit = 100): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->campaignsTable} ORDER BY created_at DESC LIMIT %d", $limit)
        ) ?: [];
    }

    public function setCampaignStatus(int $campaignId, string $status): void
    {
        $this->wpdb->update($this->campaignsTable, ['status' => $status], ['id' => $campaignId], ['%s'], ['%d']);
    }

    public function incrementCampaign(int $campaignId, string $field): void
    {
        $field = in_array($field, ['sent', 'failed'], true) ? $field : 'sent';
        $this->wpdb->query(
            $this->wpdb->prepare("UPDATE {$this->campaignsTable} SET {$field} = {$field} + 1 WHERE id = %d", $campaignId)
        );
    }

    /* ---------------------------------------------------------------------
     * Empfänger / Log
     * ------------------------------------------------------------------- */

    /**
     * Fügt mehrere Empfänger in Stapeln ein (eine Zeile pro Empfänger).
     */
    public function addRecipientsBulk(int $campaignId, array $recipients, string $status = 'queued'): void
    {
        if (empty($recipients)) {
            return;
        }

        foreach (array_chunk($recipients, 500) as $chunk) {
            $placeholders = [];
            $values       = [];

            foreach ($chunk as $r) {
                $placeholders[] = '(%d, %s, %s, %d, %s)';
                $values[]       = $campaignId;
                $values[]       = (string) ($r['email'] ?? '');
                $values[]       = (string) ($r['name'] ?? '');
                $values[]       = (int) ($r['order_id'] ?? 0);
                $values[]       = $status;
            }

            $sql = "INSERT INTO {$this->logTable} (campaign_id, email, name, order_id, status) VALUES "
                 . implode(', ', $placeholders);

            $this->wpdb->query($this->wpdb->prepare($sql, $values));
        }
    }

    public function getLog(int $logId)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->logTable} WHERE id = %d", $logId)
        );
    }

    public function getQueuedLogIds(int $campaignId): array
    {
        return array_map('intval', $this->wpdb->get_col(
            $this->wpdb->prepare("SELECT id FROM {$this->logTable} WHERE campaign_id = %d AND status = 'queued'", $campaignId)
        ));
    }

    public function getRecipients(int $campaignId): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$this->logTable} WHERE campaign_id = %d ORDER BY id ASC", $campaignId)
        ) ?: [];
    }

    public function updateRecipientStatus(int $logId, string $status, string $error = ''): void
    {
        $this->wpdb->update(
            $this->logTable,
            [
                'status'       => $status,
                'error'        => $error !== '' ? $error : null,
                'processed_at' => current_time('mysql'),
            ],
            ['id' => $logId],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    public function countRemaining(int $campaignId): int
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->logTable} WHERE campaign_id = %d AND status = 'queued'", $campaignId)
        );
    }
}
