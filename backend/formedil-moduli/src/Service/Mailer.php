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
 * Le tre email previste:
 *   1. praticaInserita()      al richiedente, con il PDF da firmare. Cc a FORMEDIL:
 *                             è il segnale che è arrivata una nuova richiesta.
 *   2. documentiRicevuti()    al solo richiedente: conferma di ricezione.
 *   3. documentiPerVerifica() alle sole caselle FORMEDIL, con i documenti firmati
 *                             allegati, così la segreteria li ha in casella.
 *
 * Configurazione (filtri WordPress):
 *   - formedil_mail_from        mittente (default no-reply@<dominio>)
 *   - formedil_mail_from_name   nome mittente (default "FORMEDIL Lecce")
 *   - formedil_admin_email      caselle interne FORMEDIL (default admin_email WP)
 *   - formedil_mail_logo_url    logo nell'intestazione (default: asset del plugin)
 *   - formedil_mail_max_attach  tetto agli allegati della notifica interna, in byte
 */
final class Mailer
{
    /** Arancio brand FORMEDIL Lecce. */
    private const BRAND = '#D35D13';

    /**
     * Tetto complessivo agli allegati della notifica interna.
     *
     * Il plugin accetta fino a 40MB di upload, ma i server SMTP rifiutano
     * tipicamente oltre i 25MB (l'encoding base64 gonfia i file di circa un
     * terzo). Restando a 20MB l'email parte sempre; se i documenti superano la
     * soglia si allega il solo PDF firmato e si rimanda al pannello.
     */
    private const MAX_ATTACH_TOTAL = 20 * 1024 * 1024;

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
            . '<p>La Sua richiesta di collaborazione ai sensi dell\'<strong>art. 37, comma 12</strong> è stata registrata correttamente.</p>'
            . '<p>In allegato trova il <strong>PDF della richiesta</strong>. Lo firmi, a mano oppure con firma digitale, e lo ricarichi dal collegamento qui sotto per completare la pratica.</p>'
            . self::codeBox($token)
            . $bottone
            . '<p style="color:#64748B;font-size:13px;">Se il pulsante non funziona, copi e incolli questo indirizzo nel browser:<br>'
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
     * Email "documenti firmati ricevuti": conferma al SOLO richiedente.
     *
     * Niente Cc a FORMEDIL: le caselle interne ricevono la notifica dedicata
     * con i documenti allegati (vedi documentiPerVerifica), che è più utile e
     * evita due messaggi quasi identici per ogni pratica.
     *
     * @param array<string,mixed> $dati
     */
    public static function documentiRicevuti(array $dati, string $token): void
    {
        $to = self::recipient($dati);
        if ($to === '') {
            // Nessun indirizzo del richiedente: non c'è nessuno da avvisare qui.
            return;
        }

        $azienda = trim((string) ($dati['azienda_ragione_sociale'] ?? '')) ?: trim((string) ($dati['org_ragione_sociale'] ?? ''));
        $intro = $azienda !== ''
            ? sprintf('Gentile %s,', esc_html($azienda))
            : 'Gentile richiedente,';

        $corpo = '<p>' . $intro . '</p>'
            . '<p>Abbiamo ricevuto correttamente il <strong>PDF firmato</strong> e gli eventuali allegati della Sua richiesta.</p>'
            . self::codeBox($token)
            . '<p>Entro <strong>15 giorni</strong> riceverà da FORMEDIL Lecce aggiornamenti sull\'esito della Sua richiesta.</p>'
            . '<p>Ai sensi dell\'ASR del 17/04/2025, qualora non riceva riscontro, può procedere autonomamente alla '
            . 'pianificazione e realizzazione dell\'attività formativa.</p>'
            . '<p style="color:#64748B;font-size:13px;">Non è necessario inviare altro: questa email conferma la ricezione dei documenti.</p>';

        $html = self::layout('Documenti ricevuti', $corpo);

        self::send(
            [$to],
            'FORMEDIL Lecce · Documenti ricevuti (Cod. ' . $token . ')',
            $html,
            [],
            'conferma al richiedente'
        );
    }

    /**
     * Email interna: i documenti firmati arrivano in casella a FORMEDIL.
     *
     * Destinatari le sole caselle interne (nessun Cc al richiedente). Allega il
     * PDF firmato e, se il peso complessivo lo consente, anche gli altri file
     * caricati; in caso contrario allega il solo firmato e rimanda al pannello.
     *
     * @param array<string,mixed>            $dati
     * @param array<int,array<string,mixed>> $allegati elenco da Repository::listAllegati
     *                                                 (chiavi usate: tipo, original_name, path)
     */
    public static function documentiPerVerifica(array $dati, string $token, array $allegati, string $dettaglioUrl = ''): void
    {
        $destinatari = self::adminEmails();
        if ($destinatari === []) {
            return;
        }

        $azienda = trim((string) ($dati['azienda_ragione_sociale'] ?? '')) ?: trim((string) ($dati['org_ragione_sociale'] ?? ''));
        $selezione = self::selezionaAllegati($allegati);

        $corpo = '<p>Sono stati caricati i documenti firmati per la pratica indicata qui sotto.</p>'
            . self::codeBox($token)
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">'
            . self::riga('Richiedente', $azienda !== '' ? $azienda : 'non indicato')
            . self::riga('Documenti caricati', (string) count($allegati))
            . '</table>'
            . self::elencoFile($selezione['inclusi'], $selezione['esclusi']);

        if ($dettaglioUrl !== '') {
            $corpo .= self::button($dettaglioUrl, 'Apri la pratica nel pannello');
        }

        $html = self::layout('Documenti da verificare', $corpo);

        self::send(
            $destinatari,
            'FORMEDIL Lecce · Documenti firmati da verificare (Cod. ' . $token . ')',
            $html,
            $selezione['paths'],
            'notifica interna'
        );
    }

    /** Esiti possibili del riscontro (S15). */
    public const ESITO_ACCETTATA = 'ACCETTATA';
    public const ESITO_CON_INDICAZIONI = 'CON_INDICAZIONI';
    public const ESITO_NON_ACCOLTA = 'NON_ACCOLTA';

    /**
     * Etichette degli esiti, usate nel pannello e nella cronologia.
     *
     * @return array<string,string>
     */
    public static function esiti(): array
    {
        return [
            self::ESITO_ACCETTATA        => 'Collaborazione accettata',
            self::ESITO_CON_INDICAZIONI  => 'Accolta con indicazioni',
            self::ESITO_NON_ACCOLTA      => 'Non accolta',
        ];
    }

    /**
     * Email di riscontro alla richiesta di collaborazione (S15).
     *
     * Tre esiti previsti dall'organismo paritetico: accettazione piena,
     * accoglimento con prescrizioni da rispettare, oppure rifiuto motivato.
     * Negli ultimi due casi il testo scritto dalla segreteria è la parte
     * sostanziale della comunicazione, quindi viene messo in evidenza.
     *
     * Restituisce l'esito dell'invio: il pannello lo mostra all'operatore, che
     * sta compiendo un'azione deliberata e deve sapere se è andata a buon fine.
     *
     * @param array<string,mixed> $dati
     */
    public static function riscontro(array $dati, string $token, string $esito, string $indicazioni = ''): bool
    {
        $to = self::recipient($dati);
        if ($to === '' || !is_email($to)) {
            error_log('[formedil] riscontro non inviato, richiedente senza email: ' . $token);
            return false;
        }

        $html = self::anteprimaRiscontro($dati, $token, $esito, $indicazioni);

        try {
            $ok = (bool) wp_mail(
                $to,
                'FORMEDIL Lecce · Riscontro alla richiesta (Cod. ' . $token . ')',
                $html,
                self::headers()
            );
            if (!$ok) {
                error_log('[formedil] wp_mail riscontro fallito: ' . $token . ' -> ' . $to);
            }
            return $ok;
        } catch (\Throwable $e) {
            error_log('[formedil] Riscontro eccezione: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Compone il messaggio di riscontro senza inviarlo.
     *
     * Serve al pannello per mostrare l'anteprima: così l'operatore vede
     * esattamente lo stesso HTML che riceverà il richiedente, e non una
     * ricostruzione approssimativa che rischia di divergere nel tempo.
     *
     * @param array<string,mixed> $dati
     */
    public static function anteprimaRiscontro(array $dati, string $token, string $esito, string $indicazioni = ''): string
    {
        $azienda = trim((string) ($dati['azienda_ragione_sociale'] ?? '')) ?: trim((string) ($dati['org_ragione_sociale'] ?? ''));

        $corpo = '<p style="font-size:14px;color:#475569;margin:0 0 18px;">'
            . '<strong>Oggetto:</strong> riscontro alla richiesta di collaborazione per la pianificazione e la '
            . 'realizzazione di corsi di formazione ai sensi dell\'art. 37, comma 12, D.Lgs. 81/08.'
            . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">'
            . self::riga('Richiedente', $azienda !== '' ? $azienda : 'non indicato')
            . self::riga('Codice richiesta', $token)
            . '</table>'
            . '<p>Gentile Datore di Lavoro / Ente di Formazione,</p>'
            . '<p>in merito alla Sua richiesta di collaborazione inviata ai sensi dell\'art. 37, comma 12, del '
            . 'D.Lgs. 81/08 per lo svolgimento delle attività formative sulla sicurezza nei luoghi di lavoro, il '
            . 'nostro Organismo Paritetico comunica quanto segue.</p>'
            . self::bloccoEsito($esito, trim($indicazioni))
            . '<p style="margin-top:22px;">Distinti saluti,<br><strong>FORMEDIL Lecce</strong><br>'
            . '<span style="color:#64748B;font-size:13px;">Ente Unico Formazione e Sicurezza — Lecce</span></p>';

        return self::layout('Riscontro alla richiesta', $corpo);
    }

    /**
     * Testo dell'esito, con il riquadro delle indicazioni quando previsto.
     *
     * Il colore del riquadro distingue a colpo d'occhio l'accoglimento con
     * prescrizioni (ambra) dal rifiuto (rosso).
     */
    private static function bloccoEsito(string $esito, string $indicazioni): string
    {
        switch ($esito) {
            case self::ESITO_ACCETTATA:
                return '<p style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:14px 18px;">'
                    . 'La collaborazione è <strong>espressamente accettata</strong>. La formazione può essere erogata '
                    . 'secondo la pianificazione inviata, nel rispetto dei contenuti e della durata minima previsti '
                    . 'dalle normative vigenti.</p>';

            case self::ESITO_CON_INDICAZIONI:
                return '<p style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:14px 18px;">'
                    . 'La richiesta è <strong>accolta</strong>, a condizione che nella pianificazione ed erogazione dei '
                    . 'corsi siano adottate le seguenti indicazioni:</p>'
                    . self::testoLibero($indicazioni);

            case self::ESITO_NON_ACCOLTA:
                return '<p style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 18px;">'
                    . 'La richiesta di collaborazione <strong>non può essere accolta</strong> in quanto:</p>'
                    . self::testoLibero($indicazioni);

            default:
                return '';
        }
    }

    /**
     * Testo scritto dalla segreteria: va mostrato a capo come è stato digitato,
     * senza interpretare eventuale HTML.
     */
    private static function testoLibero(string $testo): string
    {
        if ($testo === '') {
            return '';
        }

        return '<div style="border-left:3px solid ' . self::BRAND . ';padding:4px 0 4px 16px;margin:16px 0;'
            . 'font-size:15px;line-height:1.6;">'
            . nl2br(esc_html($testo))
            . '</div>';
    }

    /**
     * Email di prova inviata dal pannello impostazioni.
     *
     * A differenza delle notifiche vere qui interessa sapere se l'invio è
     * riuscito, per mostrarlo subito a chi sta configurando: è l'unico metodo
     * pubblico che restituisce un esito.
     */
    public static function inviaProva(string $to): bool
    {
        if ($to === '' || !is_email($to)) {
            return false;
        }

        $destinatari = self::adminEmails();
        $elenco = $destinatari !== [] ? implode(', ', $destinatari) : 'nessuno configurato';

        $corpo = '<p>Questa è un\'email di prova inviata dal pannello di FORMEDIL Lecce.</p>'
            . '<p>Se la stai leggendo, il server di posta è configurato correttamente e le notifiche automatiche possono partire.</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;font-size:14px;">'
            . self::riga('Mittente', self::fromAddress())
            . self::riga('Destinatari interni', $elenco)
            . self::riga('Inviata il', gmdate('d/m/Y H:i') . ' UTC')
            . '</table>'
            . '<p style="color:#64748B;font-size:13px;">Nessuna pratica è stata creata: questo messaggio non compare nel registro delle richieste.</p>';

        try {
            return (bool) wp_mail(
                $to,
                'FORMEDIL Lecce · Email di prova',
                self::layout('Email di prova', $corpo),
                self::headers()
            );
        } catch (\Throwable $e) {
            error_log('[formedil] Email di prova fallita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decide quali allegati stanno nell'email senza superare il tetto SMTP.
     *
     * Il PDF firmato ha la precedenza assoluta: entra sempre, anche se da solo
     * sfora. Gli altri file si aggiungono finché c'è spazio; quelli scartati
     * vengono elencati nel corpo con l'invito a scaricarli dal pannello.
     *
     * @param array<int,array<string,mixed>> $allegati
     * @return array{paths:array<int,string>,inclusi:array<int,string>,esclusi:array<int,string>}
     */
    private static function selezionaAllegati(array $allegati): array
    {
        $limite = (int) apply_filters('formedil_mail_max_attach', self::MAX_ATTACH_TOTAL);

        // Prima il documento firmato, poi gli altri: l'ordine determina la priorità.
        usort($allegati, static function (array $a, array $b): int {
            $pa = (string) ($a['tipo'] ?? '') === 'FIRMATO' ? 0 : 1;
            $pb = (string) ($b['tipo'] ?? '') === 'FIRMATO' ? 0 : 1;
            return $pa <=> $pb;
        });

        $paths = [];
        $inclusi = [];
        $esclusi = [];
        $totale = 0;

        foreach ($allegati as $a) {
            $path = (string) ($a['path'] ?? '');
            $nome = (string) ($a['original_name'] ?? basename($path));

            if ($path === '' || !is_file($path)) {
                $esclusi[] = $nome;
                continue;
            }

            $size = (int) filesize($path);
            $primo = $paths === [];

            // Il primo file (il firmato) entra comunque: meglio un'email pesante
            // che una notifica senza il documento principale.
            if (!$primo && $totale + $size > $limite) {
                $esclusi[] = $nome;
                continue;
            }

            $paths[] = $path;
            $inclusi[] = $nome;
            $totale += $size;
        }

        return ['paths' => $paths, 'inclusi' => $inclusi, 'esclusi' => $esclusi];
    }

    /**
     * Elenco dei file allegati e, se ce ne sono, di quelli lasciati fuori.
     *
     * @param array<int,string> $inclusi
     * @param array<int,string> $esclusi
     */
    private static function elencoFile(array $inclusi, array $esclusi): string
    {
        $html = '';

        if ($inclusi !== []) {
            $html .= '<p style="margin-bottom:6px;"><strong>In allegato a questa email:</strong></p><ul style="margin:0 0 18px;padding-left:20px;">';
            foreach ($inclusi as $nome) {
                $html .= '<li>' . esc_html($nome) . '</li>';
            }
            $html .= '</ul>';
        }

        if ($esclusi !== []) {
            $html .= '<p style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;font-size:14px;">'
                . '<strong>Non allegati per dimensione:</strong><br>'
                . esc_html(implode(', ', $esclusi))
                . '<br><span style="color:#92400E;">Sono scaricabili dal pannello, nel dettaglio della pratica.</span></p>';
        }

        return $html;
    }

    /** Riga etichetta/valore per le tabelle di riepilogo. */
    private static function riga(string $label, string $valore): string
    {
        return '<tr>'
            . '<td style="padding:6px 12px 6px 0;color:#64748B;width:170px;">' . esc_html($label) . '</td>'
            . '<td style="padding:6px 0;font-weight:600;">' . esc_html($valore) . '</td>'
            . '</tr>';
    }

    /**
     * Spedisce al destinatario (se presente) e in copia a FORMEDIL.
     * Inghiotte ogni errore: registra nel log ma non interrompe il chiamante.
     *
     * @param array<int,string> $attachments
     */
    private static function dispatch(string $to, string $subject, string $html, array $attachments = []): void
    {
        $admins = self::adminEmails();

        // Destinatario principale: il richiedente (se ha un'email valida).
        if ($to !== '' && is_email($to)) {
            // In Cc tutte le caselle FORMEDIL, tranne quella eventualmente già
            // usata come destinatario principale (niente doppioni).
            $cc = array_values(array_filter($admins, static function (string $a) use ($to): bool {
                return strcasecmp($a, $to) !== 0;
            }));
            self::send([$to], $subject, $html, $attachments, 'richiedente', $cc);
            return;
        }

        // Nessuna email richiedente: notifica almeno FORMEDIL.
        self::send($admins, $subject, $html, $attachments, 'FORMEDIL');
    }

    /**
     * Unico punto che chiama wp_mail. Non lancia mai: se l'invio fallisce
     * registra nel log e il flusso chiamante prosegue.
     *
     * @param array<int,string> $to          destinatari (vuoto = non invia)
     * @param array<int,string> $attachments percorsi su disco
     * @param string            $contesto    etichetta usata nei messaggi di log
     * @param array<int,string> $cc          indirizzi in copia
     */
    private static function send(array $to, string $subject, string $html, array $attachments = [], string $contesto = '', array $cc = []): void
    {
        if ($to === []) {
            return;
        }

        try {
            $headers = self::headers();
            if ($cc !== []) {
                $headers[] = 'Cc: ' . implode(', ', $cc);
            }

            $ok = wp_mail($to, $subject, $html, $headers, $attachments);
            if (!$ok) {
                error_log(sprintf(
                    '[formedil] wp_mail fallito (%s): %s -> %s',
                    $contesto !== '' ? $contesto : 'invio',
                    $subject,
                    implode(', ', $to)
                ));
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

    /**
     * Caselle interne FORMEDIL che ricevono in Cc ogni notifica.
     *
     * Il filtro `formedil_admin_email` accetta tre forme, per comodità di chi
     * configura e per non rompere le installazioni esistenti:
     *   - stringa singola:      'segreteria@formedillecce.it'
     *   - stringa con virgole:  'direttore@x.it, sicurezza@x.it'
     *   - array di stringhe:    ['direttore@x.it', 'sicurezza@x.it']
     *
     * Gli indirizzi non validi vengono scartati silenziosamente e i duplicati
     * rimossi, così un errore di battitura non blocca l'invio agli altri.
     *
     * @return array<int,string> lista di email valide, eventualmente vuota
     */
    private static function adminEmails(): array
    {
        $default = (string) get_option('admin_email');
        $value = apply_filters('formedil_admin_email', $default);

        $raw = is_array($value) ? $value : explode(',', (string) $value);

        $emails = [];
        foreach ($raw as $item) {
            $email = trim((string) $item);
            if ($email !== '' && is_email($email)) {
                $emails[strtolower($email)] = $email;
            }
        }

        return array_values($emails);
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
     * URL pubblico del logo mostrato nell'intestazione delle email.
     *
     * Il file viaggia col plugin (templates/assets/logo-email.png), così non
     * dipende da caricamenti manuali nella Media Library. Sovrascrivibile con
     * il filtro `formedil_mail_logo_url`.
     */
    private static function logoUrl(): string
    {
        $default = defined('FORMEDIL_PLUGIN_URL')
            ? FORMEDIL_PLUGIN_URL . 'templates/assets/logo-email.png'
            : '';
        return (string) apply_filters('formedil_mail_logo_url', $default);
    }

    /**
     * Intestazione: logo su fondo bianco, sottotitolo su banda arancio.
     *
     * Il logo è arancio e nero su bianco, quindi non può stare sulla banda
     * colorata. Se le immagini sono bloccate dal client di posta resta visibile
     * il testo alternativo, e la banda arancio sotto garantisce comunque la
     * riconoscibilità del marchio.
     */
    private static function header(string $titolo): string
    {
        $brand = self::BRAND;
        $logo = self::logoUrl();

        $marchio = $logo !== ''
            ? '<img src="' . esc_url($logo) . '" width="186" alt="FORMEDIL LECCE" '
                . 'style="display:block;border:0;outline:none;text-decoration:none;width:186px;max-width:100%;height:auto;">'
            : '<span style="font-size:20px;font-weight:700;letter-spacing:0.5px;color:' . $brand . ';">FORMEDIL LECCE</span>';

        return '<tr><td style="background:#ffffff;padding:24px 32px 18px;">' . $marchio . '</td></tr>'
            . '<tr><td style="background:' . $brand . ';padding:12px 32px;">'
            . '<span style="color:#ffffff;font-size:13px;letter-spacing:0.3px;">Moduli Art. 37 · ' . esc_html($titolo) . '</span>'
            . '</td></tr>';
    }

    /**
     * Wrapper HTML comune: intestazione col logo, corpo, footer.
     */
    private static function layout(string $titolo, string $corpo): string
    {
        $anno = gmdate('Y');

        return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#F1F5F9;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:24px 0;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" '
            . 'style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;'
            . 'font-family:Arial,Helvetica,sans-serif;color:#0F172A;">'
            . self::header($titolo)
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
