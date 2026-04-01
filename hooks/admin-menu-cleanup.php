<?php
/**
 * Admin Menu Cleanup
 * Nicht-whitelisted Hauptmenüpunkte werden serverseitig entfernt
 * und als Untermenü unter "Sonstiges" neu eingehängt — inkl. ihrer Submenüs.
 * Children mit Subeinträgen bekommen ein Flyout-Panel (rechts beim Hover).
 * Nur für Administratoren.
 */

function wha_get_whitelist(): array
{
    return [
        'index.php',
        'halteverbot-app',
        'edit.php?post_type=page',
        'upload.php',
        'edit.php',
        'woocommerce',
        'edit.php?post_type=product',
        'wha_sonstiges',
    ];
}

/**
 * Globaler Store für Parent→Children-Mapping (für JS-Flyout).
 */
$wha_flyout_data = [];

/**
 * Hilfsfunktion: Extrahiert Benachrichtigungszahl aus einem Menü-Label
 * Entfernt "Benachrichtigung" Text und extrahiert die Zahl
 */
function wha_extract_notification_count($label): array
{
    $clean_label = $label;
    $count = 0;
    
    // Suche nach Zahlen im Format "Text 12" oder "Text 12 Benachrichtigung"
    if (preg_match('/^(.+?)\s+(\d+)(?:\s+Benachrichtigung)?$/', $label, $matches)) {
        $clean_label = trim($matches[1]);
        $count = (int)$matches[2];
    } elseif (preg_match('/^(.+?)\s+(\d+)$/', $label, $matches)) {
        $clean_label = trim($matches[1]);
        $count = (int)$matches[2];
    }
    
    return [
        'label' => $clean_label,
        'count' => $count
    ];
}

/**
 * Schritt 1 (Priority 9999): "Sonstiges" registrieren.
 */
add_action('admin_menu', 'wha_cleanup_admin_menu', 9999);
function wha_cleanup_admin_menu(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    add_menu_page(
        'Sonstiges',
        'Sonstiges', // Temporär, wird später durch JS mit Counter ersetzt
        'manage_options',
        'wha_sonstiges',
        'wha_sonstiges_page',
        'dashicons-ellipsis',
        9999
    );

    remove_submenu_page('wha_sonstiges', 'wha_sonstiges');
}

/**
 * Schritt 2 (Priority 99999): Einsammeln, Submenüs übernehmen, entfernen.
 */
add_action('admin_menu', 'wha_collect_and_remove_sonstiges', 99999);
function wha_collect_and_remove_sonstiges(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $menu, $submenu, $wha_flyout_data;

    $whitelist = wha_get_whitelist();
    $total_notifications = 0; // Gesamtzähler für alle Benachrichtigungen

    foreach ($menu as $item) {
        $slug       = $item[2] ?? '';
        $label      = wp_strip_all_tags($item[0] ?? '');
        $capability = $item[1] ?? 'manage_options';

        if (empty($slug) || empty($label) || str_contains($slug, 'separator')) {
            continue;
        }

        if (in_array($slug, $whitelist, true)) {
            continue;
        }

        // Capability des Original-Eintrags prüfen — hat der User keinen Zugriff,
        // wird der Eintrag weder angezeigt noch in Sonstiges übernommen.
        if (!current_user_can($capability)) {
            remove_menu_page($slug);
            continue;
        }

        $children   = $submenu[$slug] ?? [];
        $parent_url = str_starts_with($slug, 'http') ? $slug : admin_url($slug);
        $parent_key = 'wha_ext_' . sanitize_key($slug);

        // Extrahiere Benachrichtigungszahl aus Parent-Label (falls vorhanden)
        $parent_notification = wha_extract_notification_count($label);
        $clean_parent_label = $parent_notification['label'];
        $parent_count = $parent_notification['count'];
        
        // Addiere zur Gesamtsumme
        $total_notifications += $parent_count;

        // Echte Children einsammeln — ebenfalls mit Capability-Prüfung
        $real_children = [];
        foreach ($children as $child) {
            $child_label      = wp_strip_all_tags($child[0] ?? '');
            $child_slug       = $child[2] ?? '';
            $child_capability = $child[1] ?? $capability;

            if (empty($child_label) || empty($child_slug)) {
                continue;
            }

            // Doppelter erster Eintrag (= Hauptpunkt selbst) überspringen
            if ($child_slug === $slug) {
                continue;
            }

            // Kein Zugriff → nicht aufnehmen
            if (!current_user_can($child_capability)) {
                continue;
            }

            $child_url = str_starts_with($child_slug, 'http')
                ? $child_slug
                : admin_url($child_slug);

            // Extrahiere Benachrichtigungszahl aus Child-Label
            $child_notification = wha_extract_notification_count($child_label);
            $clean_child_label = $child_notification['label'];
            $child_count = $child_notification['count'];
            
            // Addiere Child-Benachrichtigungen zur Gesamtsumme
            $total_notifications += $child_count;

            $real_children[] = [
                'label' => $clean_child_label,
                'url'   => $child_url,
                'count' => $child_count, // Speichere für JS/Display
            ];
        }

        // Parent als Submenü-Eintrag unter Sonstiges registrieren.
        // Verwende sauberes Label ohne Benachrichtigungszahl
        add_submenu_page(
            'wha_sonstiges',
            $clean_parent_label . ($parent_count ? " ($parent_count)" : ''),
            $clean_parent_label . ($parent_count ? " ($parent_count)" : ''),
            $capability,
            $parent_key,
            static function () use ($parent_url): void {
                wp_safe_redirect($parent_url);
                exit;
            }
        );

        // Hat der Parent echte Children → in den JS-Store für das Flyout
        if (!empty($real_children)) {
            $wha_flyout_data[$parent_key] = [
                'label'    => $clean_parent_label,
                'children' => $real_children,
            ];
        }

        // Serverseitig aus dem Hauptmenü entfernen
        remove_menu_page($slug);
    }
    
    // Speichere die Gesamtanzahl im globalen Bereich für JS
    global $wha_total_notifications;
    $wha_total_notifications = $total_notifications;
}

/**
 * Fallback-Seite für den Sonstiges-Hauptpunkt.
 */
function wha_sonstiges_page(): void
{
    echo '<div class="wrap"><h1>Sonstiges</h1><p>Bitte einen Unterpunkt auswählen.</p></div>';
}

/**
 * Styling + Flyout-JS
 */
add_action('admin_head', 'wha_admin_menu_styles');
function wha_admin_menu_styles(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wha_flyout_data, $wha_total_notifications;
    ?>
    <style>
        /* Halteverbot hervorheben */
        #adminmenu #toplevel_page_halteverbot-app > a {
            border-left: 3px solid #f0a500 !important;
        }

        /* Sonstiges dezent stylen */
        #adminmenu #toplevel_page_wha_sonstiges > a {
            opacity: 0.65;
            font-style: italic;
        }
        
        /* Kreis-Badge für Sonstiges Gesamtanzahl */
        #adminmenu #toplevel_page_wha_sonstiges .wp-menu-name {
            position: relative;
        }
        
        .wha-sonstiges-badge {
            display: inline-block;
            margin-left: 8px;
            background: #ca4a1f;
            color: white;
            border-radius: 12px;
            padding: 0 6px;
            font-size: 11px;
            line-height: 18px;
            font-weight: normal;
            font-style: normal;
            vertical-align: middle;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        /* Visuelle Trenner vor Gruppen */
        #adminmenu #toplevel_page_halteverbot-app::before,
        #adminmenu #toplevel_page_woocommerce::before,
        #adminmenu #toplevel_page_wha_sonstiges::before {
            content: '';
            display: block;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 6px 10px;
        }

        /* WP-Separatoren ausblenden */
        #adminmenu .wp-menu-separator {
            display: none !important;
        }
        
        /* ── SCROLLBAR FÜR SONSTIGES SUBMENU ───────────────────────── */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap {
            max-height: calc(100vh - 120px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-width: thin;
        }
        
        /* Webkit Scrollbar Styling */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap::-webkit-scrollbar {
            width: 6px;
        }
        
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Optional: Padding unten für besseren Scroll-Komfort */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu-wrap {
            padding-bottom: 12px;
        }

        /* ── Flyout-Panel ────────────────────────────────────── */
        .wha-flyout {
            position: fixed;
            z-index: 99999;
            background: #1d2327;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 4px;
            box-shadow: 3px 4px 16px rgba(0,0,0,0.45);
            min-width: 200px;
            max-width: 280px;
            padding: 6px 0;
            display: none;
        }
        
        /* Scrollbare Flyouts falls sehr viele Unterpunkte */
        .wha-flyout {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        
        .wha-flyout::-webkit-scrollbar {
            width: 6px;
        }
        
        .wha-flyout::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        
        .wha-flyout::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .wha-flyout.is-visible {
            display: block;
        }

        .wha-flyout__title {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            padding: 6px 14px 4px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 4px;
            position: sticky;
            top: 0;
            background: #1d2327;
            z-index: 1;
        }

        .wha-flyout a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 14px;
            color: rgba(240,246,252,0.85) !important;
            font-size: 13px;
            text-decoration: none !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background 0.12s, color 0.12s;
        }
        
        .wha-flyout a .flyout-badge {
            background: #ca4a1f;
            color: white;
            border-radius: 10px;
            padding: 0 5px;
            font-size: 10px;
            line-height: 16px;
            margin-left: 8px;
            flex-shrink: 0;
        }

        .wha-flyout a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff !important;
        }

        /* Kleines Pfeil-Indikator-Symbol neben Einträgen mit Flyout */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu a[data-wha-has-flyout]::after {
            content: ' ›';
            opacity: 0.45;
            font-size: 12px;
        }
        
        /* Submenu Badge Styling für Benachrichtigungen */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu a .submenu-badge {
            display: inline-block;
            background: #ca4a1f;
            color: white;
            border-radius: 10px;
            padding: 0 5px;
            font-size: 10px;
            line-height: 16px;
            margin-left: 8px;
            float: right;
        }
        
        /* Aktueller Menü-Eintrag mit Flyout - behalte Hintergrund */
        #adminmenu #toplevel_page_wha_sonstiges .wp-submenu li:hover {
            background: rgba(255,255,255,0.08);
        }
    </style>

    <script>
    (function () {
        const flyoutData = <?php echo wp_json_encode($wha_flyout_data); ?>;
        const totalNotifications = <?php echo intval($wha_total_notifications); ?>;

        let panel = null;
        let activeTrigger = null;
        let hideTimer = null;
        let isHoveringPanel = false;

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function showFlyout(triggerElement, key) {
            const data = flyoutData[key];
            if (!data) return;

            // Verstecke Timer
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }

            // Wenn bereits dasselbe Flyout sichtbar ist, nichts tun
            if (activeTrigger === triggerElement && panel.classList.contains('is-visible')) {
                return;
            }

            activeTrigger = triggerElement;
            
            panel.innerHTML =
                `<div class="wha-flyout__title">${escHtml(data.label)}</div>` +
                data.children
                    .map(c => `<a href="${escHtml(c.url)}">${escHtml(c.label)}${c.count > 0 ? `<span class="flyout-badge">${c.count}</span>` : ''}</a>`)
                    .join('');

            panel.classList.add('is-visible');

            // Positionieren
            const rect = triggerElement.getBoundingClientRect();
            let top = rect.top;
            
            // Stelle sicher, dass das Flyout nicht aus dem Viewport ragt
            const panelHeight = panel.offsetHeight;
            const viewportHeight = window.innerHeight;
            
            if (top + panelHeight > viewportHeight - 20) {
                top = viewportHeight - panelHeight - 20;
            }
            if (top < 20) {
                top = 20;
            }
            
            panel.style.top = top + 'px';
            panel.style.left = (rect.right + 6) + 'px';
        }

        function hideFlyout() {
            if (hideTimer) {
                clearTimeout(hideTimer);
            }
            
            // Nur ausblenden, wenn nicht über dem Panel oder Trigger
            if (!isHoveringPanel && !isHoveringTrigger()) {
                hideTimer = setTimeout(() => {
                    panel.classList.remove('is-visible');
                    activeTrigger = null;
                }, 50);
            }
        }
        
        function isHoveringTrigger() {
            if (!activeTrigger) return false;
            const hoveredElement = document.querySelector(':hover');
            return hoveredElement && (activeTrigger === hoveredElement || activeTrigger.contains(hoveredElement));
        }
        
        function cancelHide() {
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {

            // ── 1. Flyout-Panel erzeugen ─────────────────────────────────────
            panel = document.createElement('div');
            panel.className = 'wha-flyout';
            document.body.appendChild(panel);
            
            // Panel-Hover Events
            panel.addEventListener('mouseenter', () => {
                isHoveringPanel = true;
                cancelHide();
            });
            
            panel.addEventListener('mouseleave', () => {
                isHoveringPanel = false;
                hideFlyout();
            });

            // ── 2. "Sonstiges"-Hauptlink nicht-klickbar machen und Counter hinzufügen ──
            const sonstigesTopLink = document.querySelector(
                '#toplevel_page_wha_sonstiges > a'
            );
            if (sonstigesTopLink) {
                sonstigesTopLink.setAttribute('href', '#');
                sonstigesTopLink.addEventListener('click', e => e.preventDefault());
                
                // Füge Gesamt-Badge hinzu, wenn Benachrichtigungen vorhanden
                if (totalNotifications > 0) {
                    const menuName = sonstigesTopLink.querySelector('.wp-menu-name');
                    if (menuName && !menuName.querySelector('.wha-sonstiges-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'wha-sonstiges-badge';
                        badge.textContent = totalNotifications;
                        menuName.appendChild(badge);
                    }
                }
            }

            // ── 3. Flyout-Listener für Submenü-Einträge ───────────────────────
            const sonstigesLi = document.querySelector('#toplevel_page_wha_sonstiges');
            if (!sonstigesLi) return;

            sonstigesLi.querySelectorAll('.wp-submenu li').forEach(li => {
                const link = li.querySelector('a');
                if (!link) return;

                const href = link.getAttribute('href') || '';
                const match = href.match(/[?&]page=(wha_ext_[^&]+)/);
                if (!match) return;

                const key = decodeURIComponent(match[1]);
                if (!flyoutData[key]) return;

                link.setAttribute('data-wha-has-flyout', '1');

                // Mouseenter auf dem LI oder Link
                const showHandler = (e) => {
                    cancelHide();
                    showFlyout(link, key);
                };
                
                const hideHandler = () => {
                    hideFlyout();
                };
                
                li.addEventListener('mouseenter', showHandler);
                li.addEventListener('mouseleave', hideHandler);
                
                // Auch direkt auf dem Link für bessere Reaktionszeit
                link.addEventListener('mouseenter', showHandler);
                link.addEventListener('mouseleave', hideHandler);
            });
            
            // ── 4. Submenu-Einträge mit Benachrichtigungen stylen ─────────────
            const submenuLinks = sonstigesLi.querySelectorAll('.wp-submenu li a');
            submenuLinks.forEach(link => {
                const text = link.textContent;
                // Extrahiere Zahl in Klammern am Ende
                const match = text.match(/\s*\((\d+)\)\s*$/);
                if (match) {
                    const count = match[1];
                    const cleanText = text.replace(/\s*\(\d+\)\s*$/, '');
                    link.textContent = cleanText;
                    
                    // Füge Badge hinzu
                    const badge = document.createElement('span');
                    badge.className = 'submenu-badge';
                    badge.textContent = count;
                    link.appendChild(badge);
                }
            });
            
            // Verhindere, dass das Submenü beim Hover über das Flyout schließt
            const submenuWrap = sonstigesLi.querySelector('.wp-submenu-wrap');
            if (submenuWrap) {
                submenuWrap.addEventListener('mouseleave', (e) => {
                    // Nur ausblenden, wenn nicht zum Flyout wechseln
                    setTimeout(() => {
                        if (!isHoveringPanel && !submenuWrap.matches(':hover')) {
                            hideFlyout();
                        }
                    }, 50);
                });
            }
        });
    })();
    </script>
    <?php
}