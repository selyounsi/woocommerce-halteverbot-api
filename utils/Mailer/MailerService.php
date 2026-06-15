<?php

namespace Utils\Mailer;

/**
 * Orchestriert den Versand:
 *   - löst Empfänger auf, legt Kampagne + Log-Zeilen an
 *   - Live-Versand: stellt pro Empfänger einen Action-Scheduler-Job ein
 *   - Sandbox-Versand: protokolliert alle Empfänger, sendet EINE Beispiel-Mail
 *     an die Test-Adresse (kein echter Versand an Kunden)
 *
 * Der eigentliche Mailversand läuft über den WooCommerce-Mailer
 * (WC()->mailer()) inkl. Branding-Template (Header/Logo/Footer).
 */
class MailerService
{
    const AS_HOOK  = 'wha_mailer_send';
    const AS_GROUP = 'wha-mailer';

    private MailerLog $log;

    public function __construct()
    {
        $this->log = new MailerLog();
    }

    public function log(): MailerLog
    {
        return $this->log;
    }

    /**
     * Legt die Kampagne an und stößt den Versand an.
     *
     * @return array{success:bool, message?:string, campaign_id?:int, sandbox?:bool, count?:int}
     */
    public function createAndSend(array $data): array
    {
        $resolver   = new RecipientResolver();
        $recipients = $resolver->resolve($data['targeting'] ?? []);

        if (empty($recipients)) {
            return ['success' => false, 'message' => 'Keine gültigen Empfänger gefunden.'];
        }

        $sandbox     = !empty($data['sandbox']);
        $testAddress = sanitize_email($data['test_address'] ?? '');
        if ($sandbox && !is_email($testAddress)) {
            $testAddress = MailerSettings::getTestAddress();
        }

        $campaignId = $this->log->createCampaign([
            'subject'      => $data['subject'],
            'body'         => $data['body'],
            'attachments'  => $data['attachments'] ?? [],
            'targeting'    => $data['targeting_summary'] ?? [],
            'sandbox'      => $sandbox,
            'test_address' => $sandbox ? $testAddress : null,
            'total'        => count($recipients),
            'status'       => $sandbox ? 'completed' : 'queued',
        ]);

        /* -------------------------------------------------- Sandbox */
        if ($sandbox) {
            $this->log->addRecipientsBulk($campaignId, $recipients, 'sandbox');

            // Eine repräsentative Beispiel-Mail an die Test-Adresse.
            $this->deliver(
                $testAddress,
                $data['subject'],
                $data['body'],
                $data['attachments'] ?? [],
                $recipients[0],
                true
            );

            $this->log->setCampaignStatus($campaignId, 'completed');

            return [
                'success'     => true,
                'campaign_id' => $campaignId,
                'sandbox'     => true,
                'count'       => count($recipients),
            ];
        }

        /* -------------------------------------------------- Live */
        $this->log->addRecipientsBulk($campaignId, $recipients, 'queued');
        $logIds = $this->log->getQueuedLogIds($campaignId);

        if (function_exists('as_enqueue_async_action')) {
            foreach ($logIds as $logId) {
                as_enqueue_async_action(self::AS_HOOK, ['log_id' => $logId], self::AS_GROUP);
            }
        } else {
            // Fallback ohne Action Scheduler: direkt (synchron) versenden.
            foreach ($logIds as $logId) {
                $this->processRecipient($logId);
            }
        }

        return [
            'success'     => true,
            'campaign_id' => $campaignId,
            'sandbox'     => false,
            'count'       => count($recipients),
        ];
    }

    /**
     * Versendet an einen einzelnen Empfänger (vom Action Scheduler aufgerufen).
     */
    public function processRecipient(int $logId): void
    {
        $row = $this->log->getLog($logId);
        if (!$row || $row->status !== 'queued') {
            return;
        }

        $campaign = $this->log->getCampaign((int) $row->campaign_id);
        if (!$campaign) {
            $this->log->updateRecipientStatus($logId, 'failed', 'Kampagne nicht gefunden.');
            return;
        }

        $attachments = json_decode($campaign->attachments ?: '[]', true) ?: [];
        $recipient   = [
            'email'    => $row->email,
            'name'     => (string) $row->name,
            'order_id' => (int) $row->order_id,
        ];

        $ok = $this->deliver($row->email, $campaign->subject, $campaign->body, $attachments, $recipient, false);

        if ($ok) {
            $this->log->updateRecipientStatus($logId, 'sent');
            $this->log->incrementCampaign((int) $row->campaign_id, 'sent');
        } else {
            $this->log->updateRecipientStatus($logId, 'failed', 'wp_mail() lieferte false zurück.');
            $this->log->incrementCampaign((int) $row->campaign_id, 'failed');
        }

        if ($this->log->countRemaining((int) $row->campaign_id) === 0) {
            $this->log->setCampaignStatus((int) $row->campaign_id, 'completed');
        }
    }

    /**
     * Sendet eine sofortige Test-/Vorschaumail (für den "Testmail senden"-Button).
     */
    public function sendTest(string $to, string $subject, string $body, array $attachments = []): bool
    {
        if (!is_email($to)) {
            return false;
        }

        $example = ['email' => $to, 'name' => '', 'order_id' => 0];
        return $this->deliver($to, $subject, $body, $attachments, $example, true);
    }

    /* ------------------------------------------------------------------ */

    private function deliver(string $to, string $subject, string $body, array $attachmentIds, array $recipient, bool $sandbox): bool
    {
        $subject  = $this->replacePlaceholders($subject, $recipient);
        $bodyHtml = $this->replacePlaceholders(wpautop($body), $recipient);

        if ($sandbox) {
            $subject  = '[SANDBOX] ' . $subject;
            $bodyHtml = '<p style="background:#fff3cd;border:1px solid #e0a800;padding:10px 12px;border-radius:4px;margin:0 0 16px;">'
                . '⚠ <strong>Testmodus (Sandbox)</strong> – diese Nachricht wäre an <strong>'
                . esc_html($recipient['email'] ?: '—')
                . '</strong> gegangen. Es wurde nichts an echte Kunden versendet.</p>'
                . $bodyHtml;
        }

        if (!function_exists('WC') || !WC()->mailer()) {
            return false;
        }

        $mailer  = WC()->mailer();
        $message = $mailer->wrap_message($subject, $bodyHtml);
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $paths = [];
        foreach ($attachmentIds as $attachmentId) {
            $path = get_attached_file((int) $attachmentId);
            if ($path && file_exists($path)) {
                $paths[] = $path;
            }
        }

        return (bool) $mailer->send($to, $subject, $message, $headers, $paths);
    }

    private function replacePlaceholders(string $text, array $recipient): string
    {
        $name  = trim((string) ($recipient['name'] ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : '';
        $order = !empty($recipient['order_id']) ? wc_get_order((int) $recipient['order_id']) : null;

        $map = [
            '{name}'         => $name !== '' ? $name : 'Kundin/Kunde',
            '{first_name}'   => $first !== '' ? $first : 'Kundin/Kunde',
            '{email}'        => (string) ($recipient['email'] ?? ''),
            '{order_number}' => $order ? (string) $order->get_order_number() : '',
            '{order_id}'     => !empty($recipient['order_id']) ? (string) $recipient['order_id'] : '',
        ];

        return strtr($text, $map);
    }
}
