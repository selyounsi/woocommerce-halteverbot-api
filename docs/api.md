## API Documentation

Die folgenden benutzerdefinierten REST API-Endpunkte wurden erstellt:

### 1. **Abrufen von Dateien für eine Bestellung**
   - **Route**: `GET /wc/v3/order-files/{order_id}`
   - **Beschreibung**: Ruft die URLs für hochgeladene Dateien (Antrag, Genehmigung, Ablehnung, Negativliste) für eine Bestellung ab.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
   - **Antwort**:
     - Gibt die URLs der hochgeladenen Dateien zurück.

### 2. **Aktualisieren von Dateien für eine Bestellung**
   - **Route**: `POST /wc/v3/order-files/{order_id}`
   - **Beschreibung**: Aktualisiert die hochgeladenen Dateien (Antrag, Genehmigung, Ablehnung, Negativliste) für eine Bestellung.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `files` (Pflicht): Die Dateien, die hochgeladen oder aktualisiert werden sollen.
   - **Antwort**:
     - Erfolgsnachricht, wenn die Dateien erfolgreich aktualisiert wurden.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 3. **Generieren der Negativliste als PDF**
   - **Route**: `POST /wc/v3/generate-negative-list/{order_id}`
   - **Beschreibung**: Erstellt eine Negativliste als PDF und speichert die URL in den Metadaten der Bestellung.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
   - **Antwort**:
     - Erfolgsnachricht mit der URL der generierten PDF-Datei.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 4. **Abrufen von Verkehrssicherungsmaßnahmen für eine Bestellung**
   - **Route**: `GET /wc/v3/order-traffic-measures/{order_id}`
   - **Beschreibung**: Ruft die Verkehrssicherungsmaßnahmen für eine Bestellung ab (z.B. benötigte Schilder, Leuchten, etc.).
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
   - **Antwort**:
     - Gibt die Verkehrssicherungsmaßnahmen zurück (Schilder, Leuchten, etc.) oder eine leere Antwort, wenn keine Maßnahmen definiert sind.

### 5. **Aktualisieren von Verkehrssicherungsmaßnahmen für eine Bestellung**
   - **Route**: `POST /wc/v3/order-traffic-measures/{order_id}`
   - **Beschreibung**: Aktualisiert die Verkehrssicherungsmaßnahmen für eine Bestellung.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `traffic_measures` (Pflicht): Ein Array von Verkehrssicherungsmaßnahmen (z.B. Schilder, Leuchten).
   - **Antwort**:
     - Erfolgsnachricht, wenn die Verkehrssicherungsmaßnahmen erfolgreich aktualisiert wurden.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 6. **Hochladen oder Aktualisieren von Dateien für eine Bestellung**
   - **Route**: `POST /wc/v3/order-traffic-measures/{order_id}/files`
   - **Beschreibung**: Lädt Dateien hoch oder aktualisiert diese für die Verkehrssicherungsmaßnahmen einer Bestellung.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `file` (Pflicht): Die hochgeladene Datei (z.B. Dokumente zur Verkehrssicherung).
   - **Antwort**:
     - Erfolgsnachricht mit der URL der hochgeladenen Datei.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 7. **Löschen einer Datei für eine Bestellung**
   - **Route**: `DELETE /wc/v3/order-traffic-measures/{order_id}/files`
   - **Beschreibung**: Löscht eine Datei, die mit den Verkehrssicherungsmaßnahmen einer Bestellung verknüpft ist.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `file_url` (Pflicht): Die URL der zu löschenden Datei.
   - **Antwort**:
     - Erfolgsnachricht, wenn die Datei erfolgreich gelöscht wurde.
     - Fehlernachricht, wenn die Datei nicht gefunden wird oder ein Fehler auftritt.

### 8. **Abrufen des WPCA Meta**
   - **Route**: `GET /wc/v3/wcpa/get_field/{id}`
   - **Beschreibung**: Ruft das WPCA Meta-Feld für eine angegebene ID ab.
   - **Berechtigung**: Der Benutzer muss über die Berechtigung `manage_woocommerce` verfügen.
   - **Antwort**:
     - Gibt das WPCA Meta-Feld zurück.

### 9. **Abrufen von Bestellpositionen**
   - **Route**: `GET /wc/v3/order/{order_id}/wcpa/{item_id}`
   - **Beschreibung**: Ruft die WPCA Meta-Daten für ein bestimmtes Produkt innerhalb einer Bestellung ab.
   - **Berechtigung**: Der Benutzer muss über die Berechtigung `manage_woocommerce` verfügen.
   - **Antwort**:
     - Gibt die WPCA Meta-Daten für das Produkt zurück.

### 10. **Speichern von Bestellpositionen**
   - **Route**: `POST /wc/v3/order/{order_id}/wcpa/{item_id}`
   - **Beschreibung**: Speichert WPCA Meta-Daten für ein bestimmtes Produkt innerhalb einer Bestellung.
   - **Berechtigung**: Der Benutzer muss über die Berechtigung `manage_woocommerce` verfügen.
   - **Antwort**:
     - Erfolgsnachricht, wenn die WPCA Meta-Daten erfolgreich gespeichert wurden.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 11. **Abrufen von Bestellprotokollen**
   - **Route**: `GET /wc/v3/order-protocols/{order_id}`
   - **Beschreibung**: Ruft die Protokolle für eine Bestellung ab, einschließlich Lizenzen und Dateien.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
   - **Antwort**:
     - Gibt die Protokolle (Lizenzen und Dateien) für die Bestellung zurück.

### 12. **Aktualisieren, Hinzufügen oder Löschen von Lizenzen für eine Bestellung**
   - **Route**: `POST /wc/v3/order-protocols/{order_id}/licenses`
   - **Beschreibung**: Aktualisiert, fügt neue Lizenzen hinzu oder löscht Lizenzen für eine Bestellung. Diese Lizenzen können als Fahrzeugkennzeichen oder andere relevante Lizenzdaten betrachtet werden.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `licenses` (Pflicht): Die Lizenzen, die hinzugefügt oder aktualisiert werden sollen. Dies könnte beispielsweise Fahrzeugkennzeichen oder andere relevante Lizenzdaten sein.
   - **Antwort**:
     - Erfolgsnachricht, wenn die Lizenzen erfolgreich aktualisiert wurden.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 13. **Löschen einer Lizenz für eine Bestellung**
   - **Route**: `DELETE /wc/v3/order-protocols/{order_id}/licenses/{license_plate}`
   - **Beschreibung**: Löscht eine spezifische Lizenz (z. B. ein Fahrzeugkennzeichen) für eine Bestellung.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `license_plate` (Pflicht): Das Kennzeichen der zu löschenden Lizenz.
   - **Antwort**:
     - Erfolgsnachricht, wenn die Lizenz erfolgreich gelöscht wurde.
     - Fehlernachricht, wenn die Lizenz nicht gefunden wird oder ein Fehler auftritt.

### 14. **Hochladen oder Aktualisieren einer Datei für eine Bestellung**
   - **Route**: `POST /wc/v3/order-protocols/{order_id}/files`
   - **Beschreibung**: Lädt eine Datei hoch oder aktualisiert diese für eine Bestellung. Die Datei könnte Protokolle oder andere wichtige Dokumente beinhalten, die mit den Fahrzeugkennzeichen oder Bestellinformationen zusammenhängen.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `file` (Pflicht): Die hochgeladene Datei.
   - **Antwort**:
     - Erfolgsnachricht mit der URL der hochgeladenen Datei.
     - Fehlernachricht, wenn ein Fehler auftritt.

### 15. **Löschen einer Datei für eine Bestellung**
   - **Route**: `DELETE /wc/v3/order-protocols/{order_id}/files`
   - **Beschreibung**: Löscht eine Datei, die mit den Protokollen einer Bestellung verknüpft ist, wie zum Beispiel Dateien, die mit den Fahrzeugkennzeichen oder anderen Bestellinformationen zusammenhängen.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `file_url` (Pflicht): Die URL der zu löschenden Datei.
   - **Antwort**:
     - Erfolgsnachricht, wenn die Datei erfolgreich gelöscht wurde.
     - Fehlernachricht, wenn die Datei nicht gefunden wird oder ein Fehler auftritt.

### 16. **Senden eines Angebots per E-Mail an den Kunden**
   - **Route**: `POST /wc/v3/email/offer`
   - **Beschreibung**: Sendet ein Angebot per E-Mail an den Kunden. Diese Route ermöglicht es, eine Angebots-E-Mail mit einer Datei (z. B. als Anhang) zu versenden.
   - **Parameter**:
     - `to` (Pflicht): Die E-Mail-Adresse des Empfängers.
     - `number` (Pflicht): Die Angebotsnummer.
     - `attachment` (Optional): Eine Datei, die als Anhang versendet werden soll.
   - **Antwort**:
     - Erfolgsnachricht, wenn die E-Mail erfolgreich versendet wurde.
     - Fehlernachricht, wenn ein Fehler auftritt (z. B. fehlende Parameter oder Dateifehler).

### 17. **Senden einer Rechnung per E-Mail an den Kunden**
   - **Route**: `POST /wc/v3/email/invoice`
   - **Beschreibung**: Sendet eine Rechnung per E-Mail an den Kunden. Diese Route ermöglicht es, eine Rechnungs-E-Mail mit einer Datei (z. B. als Anhang) zu versenden.
   - **Parameter**:
     - `to` (Pflicht): Die E-Mail-Adresse des Empfängers.
     - `number` (Pflicht): Die Rechnungsnummer.
     - `attachment` (Optional): Eine Datei, die als Anhang versendet werden soll.
   - **Antwort**:
     - Erfolgsnachricht, wenn die E-Mail erfolgreich versendet wurde.
     - Fehlernachricht, wenn ein Fehler auftritt (z. B. fehlende Parameter oder Dateifehler).

### 18. **Aktualisieren des E-Mail-Benachrichtigungsstatus für eine Bestellung**
   - **Route**: `POST /wc/v3/orders/{order_id}/email-notification`
   - **Beschreibung**: Aktualisiert den Status der E-Mail-Benachrichtigung für eine Bestellung. Diese Route ermöglicht es, festzulegen, ob die E-Mail-Benachrichtigung für eine Bestellung aktiviert oder deaktiviert werden soll.
   - **Parameter**:
     - `order_id` (Pflicht): Die ID der Bestellung.
     - `status` (Pflicht): Der Status der E-Mail-Benachrichtigung (`true` oder `false`).
   - **Antwort**:
     - Erfolgsnachricht mit dem aktuellen Status der E-Mail-Benachrichtigung.
     - Fehlernachricht, wenn die Bestellung nicht gefunden wird oder ein Fehler auftritt.

### 19. **Senden einer benutzerdefinierten E-Mail**
   - **Route**: `POST /wc/v3/email/custom`
   - **Beschreibung**: Sendet eine benutzerdefinierte E-Mail an einen Empfänger. Diese Route ermöglicht es, eine E-Mail mit benutzerdefinierten Inhalten zu versenden, ohne eine spezielle E-Mail-Klasse zu verwenden.
   - **Parameter**:
     - `to` (Pflicht): Die E-Mail-Adresse des Empfängers.
     - `subject` (Pflicht): Der Betreff der E-Mail.
     - `message` (Pflicht): Der Inhalt der E-Mail.
   - **Antwort**:
     - Erfolgsnachricht, wenn die E-Mail erfolgreich versendet wurde.
     - Fehlernachricht, wenn ein Fehler auftritt.