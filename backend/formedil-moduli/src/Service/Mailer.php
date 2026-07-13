<?php

declare(strict_types=1);

namespace Formedil\Moduli\Service;

/**
 * Invio delle email transazionali (S7) tramite wp_mail.
 *
 * Unico punto in cui si compongono e spediscono email: i service di dominio
 * (RichiestaService, InvioService) chiamano questi metodi e non sanno nulla di
 * HTML, header o allegati.
 *
 * Robustezza: nessun metodo pubblico lancia eccezioni. Se wp_mail fallisce si
 * registra l'errore nel log e il flusso chiamante prosegue: una richiesta resta
 * valida anche se la notifica non parte.
 *
 * Configurazione (filtri WordPress):
 *   - formedil_mail_from        mittente (default no-reply@<dominio>)
 *   - formedil_mail_from_name   nome mittente (default "FORMEDIL Lecce")
 *   - formedil_admin_email      copia interna FORMEDIL (default admin_email WP)
 */
final class Mailer
{
    /** Arancio brand FORMEDIL Lecce. */
    private const BRAND = '#D35D13';

    /**
     * Email "pratica inserita": al richiedente con il PDF allegato e il link
     * per firmare/caricare. Copia a FORMEDIL.
     *
     * @param array<string,mixed> $dati
     */
    public static function praticaInserita(array $dati, string $token, string $invioUrl, string $pdfPath): void
    {
        $azienda = trim((string) ($dati['azienda_ragione_sociale'] ?? '')) ?: trim((string) ($dati['org_ragione_sociale'] ?? ''));
        $intro = $azienda !== ''
            ? sprintf('Gentile %s,', esc_html($azienda))
            : 'Gentile richiedente,';

        $bottone = self::button($invioUrl, 'Firma e carica i documenti');

        $corpo = '<p>' . $intro . '</p>'
            . '<p>La tua richiesta di collaborazione <strong>Art. 37 c.12</strong> è stata registrata correttamente.</p>'
            . '<p>In allegato trovi il <strong>PDF della richiesta</strong>: stampalo, firmalo e ricaricalo dal link qui sotto per completare la pratica.</p>'
            . self::codeBox($token)
            . $bottone
            . '<p style="color:#64748B;font-size:13px;">Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:<br>'
            . '<a href="' . esc_url($invioUrl) . '" style="color:' . self::BRAND . ';">' . esc_html($invioUrl) . '</a></p>';

        $html = self::layout('Richiesta registrata', $corpo);

        $attachments = is_file($pdfPath) ? [$pdfPath] : [];

        self::dispatch(
            self::recipient($dati),
            'FORMEDIL Lecce · Richiesta registrata (Cod. ' . $token . ')',
            $html,
            $attachments
        );
    }

    /**
     * Email "documenti firmati ricevuti": conferma al richiedente. Copia a FORMEDIL.
     *
     * @param array<string,mixed> $dati
     */
    public static function documentiRicevuti(array $dati, string $token): void
    {
        $azienda = trim((string) ($dati['azienda_ragione_sociale'] ?? '')) ?: trim((string) ($dati['org_ragione_sociale'] ?? ''));
        $intro = $azienda !== ''
            ? sprintf('Gentile %s,', esc_html($azienda))
            : 'Gentile richiedente,';

        $corpo = '<p>' . $intro . '</p>'
            . '<p>Abbiamo ricevuto correttamente il <strong>PDF firmato</strong> e gli eventuali allegati della tua richiesta.</p>'
            . self::codeBox($token)
            . '<p>La pratica è ora in carico a FORMEDIL Lecce per la verifica. Riceverai aggiornamenti sull\'esito.</p>'
            . '<p style="color:#64748B;font-size:13px;">Non è necessario inviare altro: questa email conferma la ricezione dei documenti.</p>';

        $html = self::layout('Documenti ricevuti', $corpo);

        self::dispatch(
            self::recipient($dati),
            'FORMEDIL Lecce · Documenti ricevuti (Cod. ' . $token . ')',
            $html
        );
    }

    /**
     * Spedisce al destinatario (se presente) e in copia a FORMEDIL.
     * Inghiotte ogni errore: registra nel log ma non interrompe il chiamante.
     *
     * @param array<int,string> $attachments
     */
    private static function dispatch(string $to, string $subject, string $html, array $attachments = []): void
    {
        try {
            $headers = self::headers();
            $admin = self::adminEmail();

            // Destinatario principale: il richiedente (se ha un'email valida).
            if ($to !== '' && is_email($to)) {
                $headersTo = $headers;
                if ($admin !== '' && is_email($admin) && strcasecmp($admin, $to) !== 0) {
                    $headersTo[] = 'Cc: ' . $admin;
                }
                $ok = wp_mail($to, $subject, $html, $headersTo, $attachments);
                if (!$ok) {
                    error_log('[formedil] wp_mail al richiedente fallito: ' . $subject . ' -> ' . $to);
                }
                return;
            }

            // Nessuna email richiedente: notifica almeno FORMEDIL.
            if ($admin !== '' && is_email($admin)) {
                $ok = wp_mail($admin, $subject, $html, $headers, $attachments);
                if (!$ok) {
                    error_log('[formedil] wp_mail a FORMEDIL fallito: ' . $subject . ' -> ' . $admin);
                }
            }
        } catch (\Throwable $e) {
            error_log('[formedil] Mailer eccezione: ' . $e->getMessage());
        }
    }

    /**
     * Destinatario richiedente: sempre azienda_email (presente in IMPRESA ed ENTE).
     *
     * @param array<string,mixed> $dati
     */
    private static function recipient(array $dati): string
    {
        $to = trim((string) ($dati['azienda_email'] ?? ''));
        if ($to === '') {
            // ENTE: nessuna azienda_email a livello alto -> ente formatore, poi prima impresa.
            $to = trim((string) ($dati['org_email'] ?? ''));
        }
        if ($to === '') {
            $imprese = is_array($dati['imprese'] ?? null) ? $dati['imprese'] : [];
            foreach ($imprese as $im) {
                if (is_array($im)) {
                    $e = trim((string) ($im['azienda_email'] ?? ''));
                    if ($e !== '') {
                        $to = $e;
                        break;
                    }
                }
            }
        }
        return $to;
    }

    /** @return array<int,string> */
    private static function headers(): array
    {
        $from = self::fromAddress();
        $name = self::fromName();
        return [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $name, $from),
        ];
    }

    private static function fromAddress(): string
    {
        $host = (string) wp_parse_url(get_site_url(), PHP_URL_HOST);
        $host = $host !== '' ? preg_replace('/^www\./', '', $host) : 'localhost';
        $default = 'no-reply@' . $host;
        return (string) apply_filters('formedil_mail_from', $default);
    }

    private static function fromName(): string
    {
        return (string) apply_filters('formedil_mail_from_name', 'FORMEDIL Lecce');
    }

    private static function adminEmail(): string
    {
        $default = (string) get_option('admin_email');
        return (string) apply_filters('formedil_admin_email', $default);
    }

    /** Riquadro evidenziato con il codice pratica. */
    private static function codeBox(string $token): string
    {
        $tok = esc_html($token);
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">'
            . '<tr><td style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px 18px;">'
            . '<span style="color:#64748B;font-size:13px;">Codice pratica</span><br>'
            . '<span style="font-size:20px;font-weight:700;letter-spacing:1px;color:#0F172A;">' . $tok . '</span>'
            . '</td></tr></table>';
    }

    /** Pulsante call-to-action brandizzato (table-based per i client email). */
    private static function button(string $url, string $label): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 20px;">'
            . '<tr><td style="border-radius:8px;background:' . self::BRAND . ';">'
            . '<a href="' . esc_url($url) . '" target="_blank" '
            . 'style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;'
            . 'color:#ffffff;text-decoration:none;border-radius:8px;">' . esc_html($label) . '</a>'
            . '</td></tr></table>';
    }

    /**
     * Wrapper HTML comune: header arancio con wordmark, corpo, footer.
     */
    private static function layout(string $titolo, string $corpo): string
    {
        $brand = self::BRAND;
        $anno = gmdate('Y');

        return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#F1F5F9;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:24px 0;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" '
            . 'style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;'
            . 'font-family:Arial,Helvetica,sans-serif;color:#0F172A;">'
            // Header
            . '<tr><td style="background:' . $brand . ';padding:22px 32px;">'
            . '<span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:0.5px;">FORMEDIL LECCE</span><br>'
            . '<span style="color:#ffffff;opacity:0.85;font-size:13px;">Moduli Art. 37 · ' . esc_html($titolo) . '</span>'
            . '</td></tr>'
            // Corpo
            . '<tr><td style="padding:28px 32px;font-size:15px;line-height:1.6;">' . $corpo . '</td></tr>'
            // Footer
            . '<tr><td style="padding:18px 32px;background:#F8FAFC;border-top:1px solid #E2E8F0;'
            . 'font-size:12px;color:#94A3B8;">'
            . 'Email automatica · FORMEDIL Lecce — Formazione e Sicurezza in Edilizia · &copy; ' . $anno
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
