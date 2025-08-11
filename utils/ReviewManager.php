<?php

namespace Utils;

class ReviewManager
{
    private $wpdb;
    private $table;

    private array $columns = [
        'id'              => 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
        'order_id'        => 'BIGINT(20) UNSIGNED DEFAULT NULL',
        'review_text'     => 'TEXT NOT NULL',
        'rating'          => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0',
        'referral_source' => 'VARCHAR(255) DEFAULT NULL',
        'is_shown'        => 'TINYINT(1) NOT NULL DEFAULT 0',
        'created_at'      => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'wha_reviews';
    }

    /**
     * Erstellt die Tabelle, falls sie nicht existiert
     */
    public function createTable() 
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table}'") === $this->table;
        if ($table_exists) {
            return false;
        }

        $columns_sql = [];
        foreach ($this->columns as $name => $definition) {
            $columns_sql[] = "$name $definition";
        }

        // Primärschlüssel und Index
        $columns_sql[] = 'PRIMARY KEY (id)';
        $columns_sql[] = 'KEY order_id (order_id)';

        // Tabelle erstellen (ohne Foreign Key, da dbDelta oft Probleme macht)
        $sql = "CREATE TABLE {$this->table} (
            " . implode(",\n        ", $columns_sql) . "
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Fremdschlüssel nachträglich setzen
        $posts_table = $this->wpdb->prefix . 'posts';
        $fk_sql = "
            ALTER TABLE {$this->table}
            ADD CONSTRAINT fk_review_order
            FOREIGN KEY (order_id)
            REFERENCES {$posts_table}(ID)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ";
        $this->wpdb->query($fk_sql);

        return !$this->wpdb->last_error;
    }

    public function updateTable() 
    {
        // 1. Alle aktuellen Spalten aus der Tabelle holen
        $existing_columns = $this->wpdb->get_col("SHOW COLUMNS FROM {$this->table}", 0); // Nur Spaltennamen

        // 2. Fehlende Spalten hinzufügen
        foreach ($this->columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $sql = "ALTER TABLE {$this->table} ADD COLUMN $column $definition";
                $this->wpdb->query($sql);

                if ($this->wpdb->last_error) {
                    error_log("Fehler beim Hinzufügen von Spalte {$column}: " . $this->wpdb->last_error);
                } else {
                    error_log("Spalte {$column} erfolgreich hinzugefügt.");
                }
            }
        }

        // 3. Nicht mehr benötigte Spalten entfernen
        foreach ($existing_columns as $existing_column) {
            if (!array_key_exists($existing_column, $this->columns)) {
                // Wichtige Spalten niemals löschen (z. B. Primärschlüssel)
                if (in_array($existing_column, ['id'])) {
                    continue;
                }

                $sql = "ALTER TABLE {$this->table} DROP COLUMN $existing_column";
                $this->wpdb->query($sql);

                if ($this->wpdb->last_error) {
                    error_log("Fehler beim Löschen von Spalte {$existing_column}: " . $this->wpdb->last_error);
                } else {
                    error_log("Spalte {$existing_column} wurde entfernt.");
                }
            }
        }

        return !$this->wpdb->last_error;
    }

    /**
     * Löscht die Tabelle, falls sie existiert
     */
    public function dropTable() {
        $sql = "DROP TABLE IF EXISTS {$this->table}";
        $this->wpdb->query($sql);

        return !$this->wpdb->last_error;
    }

    /**
     * Neue Bewertung speichern
     * 
     * @param array $data [
     *     'rating'         => int,
     *     'review_text'    => string,
     *     'is_shown'       => int (optional),
     *     'review_text'    => string (optional),
     *     'order_id'       => int (optional)
     * ]
     */
    public function create(array $data) {
        $defaults = [
            'order_id'          => null,
            'rating'            => 0,
            'review_text'       => '',
            'referral_source'   => '',
            'is_shown'          => 0,
        ];

        $data = array_merge($defaults, $data);

        // Sanitize
        $data['rating'] = intval($data['rating']);
        $data['is_shown'] = intval($data['is_shown']);
        $data['review_text'] = sanitize_textarea_field($data['review_text']);
        $data['referral_source'] = sanitize_textarea_field($data['referral_source']);

        if ($data['order_id'] !== null) {
            $data['order_id'] = intval($data['order_id']);
        }

        return $this->wpdb->insert(
            $this->table,
            $data,
            ['%d', '%d', '%s', '%s', '%d']
        );
    }

    /**
     * Sichtbarkeit umschalten
     */
    public function toggleVisibility($id, $visible) {
        return $this->wpdb->update(
            $this->table,
            ['is_shown' => intval($visible)],
            ['id' => intval($id)],
            ['%d'],
            ['%d']
        );
    }

    /**
     * Bewertung löschen
     */
    public function delete($id) {
        return $this->wpdb->delete(
            $this->table,
            ['id' => intval($id)],
            ['%d']
        );
    }

    /**
     * Alle Bewertungen abrufen (neueste zuerst)
     */
    public function getAll() {
        $results = $this->wpdb->get_results(
            "SELECT *
            FROM {$this->table}
            ORDER BY created_at DESC"
        );

        foreach ($results as $review) {
            $review->order = !empty($review->order_id) ? wc_get_order($review->order_id) : null;
        }

        return $results;
    }

    /**
     * Alle sichtbaren Bewertungen abrufen
     */
    public function getShownReviews() {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table} WHERE is_shown = 1 ORDER BY created_at DESC"
        );

        foreach ($results as $review) {
            $review->order = !empty($review->order_id) ? wc_get_order($review->order_id) : null;
        }

        return $results;
    }

    /**
     * Einzelne Bewertung abrufen
     */
    public function getById($id) {
        $review = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", intval($id))
        );

        if ($review) {
            $review->order = !empty($review->order_id) ? wc_get_order($review->order_id) : null;
        }

        return $review;
    }

    /**
     * Anzahl Bewertungen im Zeitraum (inkl. Start und Endzeit)
     * 
     * @param \DateTime $start
     * @param \DateTime $end
     * @return int
     */
    public function getCountByDateRange(\DateTime $start, \DateTime $end): int
    {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE created_at BETWEEN %s AND %s",
            $start->format('Y-m-d 00:00:00'),
            $end->format('Y-m-d 23:59:59')
        );

        return (int) $this->wpdb->get_var($query);
    }

    /**
     * Zusammenfassung: Anzahl Bewertungen heute, gestern, diese Woche, Monat, letztes Monat, Jahr
     * 
     * @return array
     */
    public function getStatsSummary(): array
    {
        $today = new \DateTime('today');
        $yesterday = new \DateTime('yesterday');

        $startOfWeek = (clone $today)->modify('monday this week');
        $endOfWeek = (clone $startOfWeek)->modify('sunday this week');

        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        $startOfLastMonth = new \DateTime('first day of last month');
        $endOfLastMonth = new \DateTime('last day of last month');

        $startOfYear = new \DateTime('first day of January this year');
        $endOfYear = new \DateTime('last day of December this year');

        return [
            'today'       => $this->getCountByDateRange($today, $today),
            'yesterday'   => $this->getCountByDateRange($yesterday, $yesterday),
            'this_week'   => $this->getCountByDateRange($startOfWeek, $endOfWeek),
            'this_month'  => $this->getCountByDateRange($startOfMonth, $endOfMonth),
            'last_month'  => $this->getCountByDateRange($startOfLastMonth, $endOfLastMonth),
            'this_year'   => $this->getCountByDateRange($startOfYear, $endOfYear),
        ];
    }

    /**
     * Anzahl Bewertungen je Sterne (1 bis 5)
     * 
     * @return array [star => count]
     */
    public function getStarDistribution(): array
    {
        $results = $this->wpdb->get_results(
            "SELECT rating, COUNT(*) as count FROM {$this->table} GROUP BY rating",
            ARRAY_A
        );

        $distribution = array_fill(1, 5, 0); // Default 0 für 1-5 Sterne

        foreach ($results as $row) {
            $rating = (int)$row['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $distribution[$rating] = (int)$row['count'];
            }
        }

        return $distribution;
    }

    /**
     * Anzahl Bewertungen je Referral-Quelle (referral_source)
     * 
     * @return array [referral_source => count]
     */
    public function getReferralStats(): array
    {
        $results = $this->wpdb->get_results(
            "SELECT referral_source, COUNT(*) as count FROM {$this->table} GROUP BY referral_source",
            ARRAY_A
        );

        $stats = [];
        foreach ($results as $row) {
            $key = $row['referral_source'] ?: 'Unbekannt';
            $stats[$key] = (int)$row['count'];
        }

        return $stats;
    }


    /**
     * Anzahl Bewertungen, die sichtbar sind (is_shown = 1)
     */
    public function countIsShown() {
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE is_shown = 1");
    }

    /**
     * Anzahl Bewertungen, die nicht sichtbar sind (is_shown = 0)
     */
    public function countIsNotShown() {
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE is_shown = 0");
    }

    /**
     * Gesamtanzahl aller Bewertungen
     */
    public function countAll() {
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }
}
