<?php

declare(strict_types=1);

namespace Formedil\Moduli\Admin;

use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Pdf\PdfGenerator;
use Formedil\Moduli\Service\Mailer;
use Formedil\Moduli\Service\RichiestaService;
use Formedil\Moduli\Storage\AllegatoStorage;
use Formedil\Moduli\Support\Audit;
use Formedil\Moduli\Support\Status;
use Formedil\Moduli\Support\Token;

/**
 * Gestionale dentro wp-admin.
 *
 * Usa autenticazione, permessi e nonce di WordPress: niente JWT.
 * - Menu "FORMEDIL" -> lista richieste (filtri + paginazione)
 * - Dettaglio (?token=...) -> anagrafica, allegati, azioni di stato
 * - Download allegati e cambio stato via admin-post.php (nonce + capability)
 */
final class Panel
{
    // S12: download PDF generato da wp-admin.
    public const SLUG = 'formedil-richieste';
    public const CAP = 'manage_options';
    private const PER_PAGE = 20;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_formedil_stato', [$this, 'handleStato']);
        add_action('admin_post_formedil_download', [$this, 'handleDownload']);
        add_action('admin_post_formedil_download_pdf', [$this, 'handleDownloadPdf']);
        add_action('admin_post_formedil_riscontro', [$this, 'handleRiscontro']);
    }

    public function menu(): void
    {
        add_menu_page(
            'FORMEDIL Richieste',
            'FORMEDIL',
            self::CAP,
            self::SLUG,
            [$this, 'render'],
            'dashicons-clipboard',
            26
        );
    }

    public function render(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Non hai i permessi per accedere a questa pagina.', 'formedil'));
        }

        $token = isset($_GET['token']) ? Token::normalize(sanitize_text_field(wp_unslash($_GET['token']))) : '';
        if ($token !== '') {
            // Passaggio intermedio del riscontro: si mostra l'anteprima del
            // messaggio, senza scrivere nulla finché l'operatore non conferma.
            $step = isset($_POST['formedil_step']) ? sanitize_key(wp_unslash($_POST['formedil_step'])) : '';
            if ($step === 'anteprima' && check_admin_referer('formedil_riscontro_' . $token)) {
                $row = Repository::findByToken($token);
                if ($row !== null) {
                    $this->renderAnteprima($row);
                    return;
                }
            }
            $this->renderDetail($token);
            return;
        }
        $this->renderList();
    }

    // ---------------------------------------------------------------- LISTA

    private function renderList(): void
    {
        $stato = isset($_GET['stato']) ? sanitize_text_field(wp_unslash($_GET['stato'])) : '';
        if ($stato !== '' && !Status::isValid($stato)) {
            $stato = '';
        }
        $q = isset($_GET['q']) ? Token::normalize(sanitize_text_field(wp_unslash($_GET['q']))) : '';
        $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $offset = ($paged - 1) * self::PER_PAGE;

        $rows = Repository::list($stato, $q, self::PER_PAGE, $offset);
        $total = Repository::count($stato, $q);
        $pages = (int) ceil($total / self::PER_PAGE);
        $service = new RichiestaService();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Richieste di collaborazione', 'formedil') . '</h1>';

        // Filtri (GET).
        echo '<form method="get" style="margin:16px 0;">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::SLUG) . '" />';
        echo '<select name="stato">';
        echo '<option value="">' . esc_html__('Tutti gli stati', 'formedil') . '</option>';
        foreach (Status::all() as $s) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($s),
                selected($stato, $s, false),
                esc_html(self::statoLabel($s))
            );
        }
        echo '</select> ';
        echo '<input type="search" name="q" value="' . esc_attr($q) . '" placeholder="' . esc_attr__('Cerca per token…', 'formedil') . '" /> ';
        submit_button(__('Filtra', 'formedil'), 'secondary', '', false);
        echo '</form>';

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Token', 'formedil') . '</th>';
        echo '<th>' . esc_html__('Variante', 'formedil') . '</th>';
        echo '<th>' . esc_html__('Denominazione', 'formedil') . '</th>';
        echo '<th>' . esc_html__('Stato', 'formedil') . '</th>';
        echo '<th>' . esc_html__('Creata', 'formedil') . '</th>';
        echo '</tr></thead><tbody>';

        if ($rows === []) {
            echo '<tr><td colspan="5">' . esc_html__('Nessuna richiesta trovata.', 'formedil') . '</td></tr>';
        }

        foreach ($rows as $row) {
            $r = $service->riepilogo($row);
            $detailUrl = add_query_arg(
                ['page' => self::SLUG, 'token' => $r['token']],
                admin_url('admin.php')
            );
            echo '<tr>';
            echo '<td><a href="' . esc_url($detailUrl) . '"><strong>' . esc_html($r['token']) . '</strong></a></td>';
            echo '<td>' . esc_html($r['variante']) . '</td>';
            echo '<td>' . esc_html($r['denominazione'] ?: '—') . '</td>';
            echo '<td>' . self::statoTag((string) $r['stato']) . '</td>';
            echo '<td>' . esc_html(self::formatData((string) $r['created_at'])) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Paginazione.
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo '<span class="displaying-num">' . esc_html(sprintf(_n('%d elemento', '%d elementi', $total, 'formedil'), $total)) . '</span> ';
            $base = add_query_arg(['page' => self::SLUG, 'stato' => $stato, 'q' => $q], admin_url('admin.php'));
            if ($paged > 1) {
                echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged - 1, $base)) . '">‹ ' . esc_html__('Precedente', 'formedil') . '</a> ';
            }
            echo '<span style="margin:0 8px;">' . esc_html(sprintf(__('Pagina %1$d di %2$d', 'formedil'), $paged, $pages)) . '</span>';
            if ($paged < $pages) {
                echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged + 1, $base)) . '">' . esc_html__('Successiva', 'formedil') . ' ›</a>';
            }
            echo '</div></div>';
        }

        echo '</div>';
    }

    // ------------------------------------------------------------- DETTAGLIO

    private function renderDetail(string $token): void
    {
        $row = Repository::findByToken($token);
        if ($row === null) {
            echo '<div class="wrap"><h1>' . esc_html__('Richiesta non trovata', 'formedil') . '</h1>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=' . self::SLUG)) . '">‹ ' . esc_html__('Torna alla lista', 'formedil') . '</a></p></div>';
            return;
        }

        $allegati = Repository::listAllegati((int) ($row['id'] ?? 0));
        $service = new RichiestaService();
        $det = $service->dettaglio($row, $allegati);
        $stato = (string) ($row['stato'] ?? '');

        echo '<div class="wrap">';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=' . self::SLUG)) . '">‹ ' . esc_html__('Tutte le richieste', 'formedil') . '</a></p>';
        echo '<h1>' . esc_html($token) . ' ' . self::statoTag($stato) . '</h1>';

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Stato aggiornato.', 'formedil') . '</p></div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Transizione di stato non consentita.', 'formedil') . '</p></div>';
        }
        if (isset($_GET['riscontro'])) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Riscontro inviato al richiedente e stato aggiornato.', 'formedil')
                . '</p></div>';
        }
        if (isset($_GET['mail_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Invio del riscontro non riuscito: lo stato non è stato modificato, puoi riprovare. Se il problema persiste controlla le impostazioni in WP Mail SMTP.', 'formedil')
                . '</p></div>';
        }

        // Anagrafica.
        echo '<h2>' . esc_html__('Anagrafica', 'formedil') . '</h2>';
        echo '<table class="widefat" style="max-width:640px;"><tbody>';
        self::riga(__('Variante', 'formedil'), (string) $det['variante']);
        self::riga(__('Denominazione', 'formedil'), (string) ($det['denominazione'] ?: '—'));
        self::riga(__('Tipi di corso', 'formedil'), implode(', ', (array) ($det['tipi_corso'] ?? [])) ?: '—');
        self::riga(__('Durata', 'formedil'), self::periodo((string) ($det['durata_dal'] ?? ''), (string) ($det['durata_al'] ?? '')));
        self::riga(__('Creata', 'formedil'), self::formatData((string) ($det['created_at'] ?? '')));
        self::riga(__('Aggiornata', 'formedil'), self::formatData((string) ($det['updated_at'] ?? '')));
        echo '</tbody></table>';

        // Modulo generato (PDF non firmato prodotto alla creazione).
        echo '<h2>' . esc_html__('Modulo generato', 'formedil') . '</h2>';
        $pdfFilename = 'richiesta-' . $token . '.pdf';
        $pdfPath = PdfGenerator::path($pdfFilename);
        if (is_file($pdfPath)) {
            $pdfUrl = wp_nonce_url(
                admin_url('admin-post.php?action=formedil_download_pdf&token=' . rawurlencode($token)),
                'formedil_download_pdf_' . $token
            );
            echo '<table class="wp-list-table widefat fixed striped" style="max-width:640px;"><tbody>';
            echo '<tr>';
            echo '<td><strong>' . esc_html($pdfFilename) . '</strong><br><span class="description">' . esc_html(__('Modulo generato (non firmato)', 'formedil') . ' · ' . self::formatSize((int) filesize($pdfPath))) . '</span></td>';
            echo '<td style="text-align:right;"><a class="button" href="' . esc_url($pdfUrl) . '">' . esc_html__('Scarica PDF', 'formedil') . '</a></td>';
            echo '</tr>';
            echo '</tbody></table>';
        } else {
            echo '<p class="description">' . esc_html__('Il PDF generato non è più disponibile su disco.', 'formedil') . '</p>';
        }

        // Allegati.
        echo '<h2>' . esc_html__('Documenti caricati', 'formedil') . '</h2>';
        if ($allegati === []) {
            echo '<p>' . esc_html__('Nessun documento caricato.', 'formedil') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped" style="max-width:640px;"><tbody>';
            foreach ($allegati as $a) {
                $id = (int) ($a['id'] ?? 0);
                $dlUrl = wp_nonce_url(
                    admin_url('admin-post.php?action=formedil_download&id=' . $id),
                    'formedil_download_' . $id
                );
                $tipo = ($a['tipo'] ?? '') === 'FIRMATO' ? __('Modulo firmato', 'formedil') : __('Allegato', 'formedil');
                echo '<tr>';
                echo '<td><strong>' . esc_html((string) ($a['original_name'] ?? '')) . '</strong><br><span class="description">' . esc_html($tipo . ' · ' . self::formatSize((int) ($a['size'] ?? 0))) . '</span></td>';
                echo '<td style="text-align:right;"><a class="button" href="' . esc_url($dlUrl) . '">' . esc_html__('Scarica', 'formedil') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        // Azioni di stato.
        echo '<h2>' . esc_html__('Stato', 'formedil') . '</h2>';
        $transizioni = Status::transitions()[$stato] ?? [];
        if ($transizioni === []) {
            echo '<p class="description">' . esc_html__('Nessuna azione disponibile per questo stato.', 'formedil') . '</p>';
        } elseif ($stato === Status::IN_VERIFICA) {
            // Da "in verifica" si esce comunicando l'esito al richiedente: il
            // cambio di stato e l'email di riscontro sono un'unica operazione.
            self::formRiscontro($token, (array) ($row['dati'] ?? []));
        } else {
            foreach ($transizioni as $nuovo) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:8px;">';
                echo '<input type="hidden" name="action" value="formedil_stato" />';
                echo '<input type="hidden" name="token" value="' . esc_attr($token) . '" />';
                echo '<input type="hidden" name="stato" value="' . esc_attr($nuovo) . '" />';
                wp_nonce_field('formedil_stato_' . $token);
                echo '<button type="submit" class="button button-primary">' . esc_html(self::azioneLabel($nuovo)) . '</button>';
                echo '</form>';
            }
        }

        // Cronologia (audit log).
        $eventi = Repository::listAudit((int) ($row['id'] ?? 0));
        echo '<h2>' . esc_html__('Cronologia', 'formedil') . '</h2>';
        if ($eventi === []) {
            echo '<p class="description">' . esc_html__('Nessun evento registrato.', 'formedil') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped" style="max-width:760px;">';
            echo '<thead><tr>';
            echo '<th style="width:150px;">' . esc_html__('Data', 'formedil') . '</th>';
            echo '<th>' . esc_html__('Evento', 'formedil') . '</th>';
            echo '<th style="width:160px;">' . esc_html__('Autore', 'formedil') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($eventi as $e) {
                $attore = (string) ($e['attore'] ?? '');
                $autore = $attore !== '' ? $attore : __('Richiedente', 'formedil');
                $ip = (string) ($e['ip'] ?? '');
                echo '<tr>';
                echo '<td>' . esc_html(self::formatDataOra((string) ($e['created_at'] ?? ''))) . '</td>';
                echo '<td><strong>' . esc_html(self::eventoLabel((string) ($e['evento'] ?? ''))) . '</strong>';
                if (!empty($e['dettaglio'])) {
                    echo '<br><span class="description">' . esc_html((string) $e['dettaglio']) . '</span>';
                }
                echo '</td>';
                echo '<td>' . esc_html($autore);
                if ($ip !== '') {
                    echo '<br><span class="description">' . esc_html($ip) . '</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    // -------------------------------------------------------------- RISCONTRO

    /**
     * Form di scelta dell'esito, mostrato quando la pratica è in verifica.
     *
     * Non invia nulla: porta alla schermata di anteprima, dove l'operatore
     * rilegge il messaggio prima di spedirlo.
     *
     * @param array<string,mixed> $dati
     */
    private static function formRiscontro(string $token, array $dati): void
    {
        $destinatario = self::emailRichiedente($dati);

        echo '<p class="description" style="max-width:640px;">'
            . esc_html__('Per chiudere la pratica scegli l\'esito: al richiedente arriva l\'email di riscontro e lo stato viene aggiornato di conseguenza. Prima dell\'invio vedrai un\'anteprima del messaggio.', 'formedil')
            . '</p>';

        if ($destinatario === '') {
            echo '<div class="notice notice-warning inline" style="margin:12px 0;padding:10px 14px;"><p>'
                . esc_html__('Questa richiesta non contiene un indirizzo email: il riscontro non può essere inviato. Puoi comunque cambiare stato manualmente.', 'formedil')
                . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(self::detailUrl($token)) . '">';
        wp_nonce_field('formedil_riscontro_' . $token);
        echo '<input type="hidden" name="formedil_step" value="anteprima" />';

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Esito', 'formedil') . '</th><td>';
        $primo = true;
        foreach (Mailer::esiti() as $valore => $label) {
            echo '<label style="display:block;margin-bottom:8px;">';
            echo '<input type="radio" name="esito" class="formedil-esito-radio" data-esito="' . esc_attr($valore)
                . '" value="' . esc_attr($valore) . '" ' . checked($primo, true, false) . '> ';
            echo esc_html($label);
            echo '</label>';
            $primo = false;
        }
        echo '<p class="description">' . esc_html__('Le due opzioni con indicazioni richiedono il testo qui sotto.', 'formedil') . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="formedil-indicazioni">' . esc_html__('Indicazioni o motivazione', 'formedil') . '</label></th><td>';
        echo '<textarea id="formedil-indicazioni" name="indicazioni" rows="6" class="large-text code" placeholder="'
            . esc_attr__('Testo che comparirà nell\'email, a capo come lo scrivi qui.', 'formedil') . '"></textarea>';
        echo '<p class="description">'
            . esc_html__('Obbligatorio se la richiesta è accolta con indicazioni oppure non accolta. Ignorato in caso di accettazione piena. Selezionando "Non accolta" viene proposto un testo base, modificabile.', 'formedil')
            . '</p></td></tr>';

        echo '</tbody></table>';

        submit_button(__('Prepara il riscontro', 'formedil'), 'primary', 'submit', false);
        echo '</form>';

        self::scriptPrefillDiniego();
    }

    /**
     * Precompila la textarea indicazioni con un testo base quando l'operatore
     * seleziona l'esito "Non accolta" (solo se il campo è ancora vuoto, per
     * non sovrascrivere un testo già scritto). Il testo resta liberamente
     * modificabile prima dell'invio.
     */
    private static function scriptPrefillDiniego(): void
    {
        $testo = "Gentile datore di lavoro,\n"
            . "A seguito dell'esame della documentazione inviata, dobbiamo informarVi che non ci è possibile accogliere la Vostra richiesta per le seguenti motivazioni:\n\n";
        ?>
        <script>
        (function () {
            var testo = <?php echo wp_json_encode($testo); ?>;
            var esitoNonAccolta = <?php echo wp_json_encode(Mailer::ESITO_NON_ACCOLTA); ?>;
            var area = document.getElementById('formedil-indicazioni');
            if (!area) return;
            var radios = document.querySelectorAll('.formedil-esito-radio');
            radios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (radio.checked && radio.dataset.esito === esitoNonAccolta && area.value.trim() === '') {
                        area.value = testo;
                        area.focus();
                        area.selectionStart = area.selectionEnd = area.value.length;
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Schermata di anteprima: mostra il messaggio come lo riceverà il
     * richiedente e chiede conferma. Nessuna modifica è ancora stata scritta.
     *
     * @param array<string,mixed> $row
     */
    private function renderAnteprima(array $row): void
    {
        $token = (string) ($row['token'] ?? '');
        $dati = (array) ($row['dati'] ?? []);

        $esito = isset($_POST['esito']) ? sanitize_text_field(wp_unslash($_POST['esito'])) : '';
        $indicazioni = isset($_POST['indicazioni']) ? sanitize_textarea_field(wp_unslash($_POST['indicazioni'])) : '';

        $etichette = Mailer::esiti();
        $destinatario = self::emailRichiedente($dati);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Anteprima del riscontro', 'formedil') . '</h1>';

        // Validazioni: si torna indietro senza aver toccato nulla.
        $errore = '';
        if (!isset($etichette[$esito])) {
            $errore = __('Esito non riconosciuto.', 'formedil');
        } elseif ($esito !== Mailer::ESITO_ACCETTATA && trim($indicazioni) === '') {
            $errore = __('Per questo esito il testo delle indicazioni è obbligatorio.', 'formedil');
        } elseif ($destinatario === '') {
            $errore = __('La richiesta non contiene un indirizzo email a cui inviare il riscontro.', 'formedil');
        }

        if ($errore !== '') {
            echo '<div class="notice notice-error"><p>' . esc_html($errore) . '</p></div>';
            echo '<p><a class="button" href="' . esc_url(self::detailUrl($token)) . '">'
                . esc_html__('Torna alla pratica', 'formedil') . '</a></p></div>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped" style="max-width:760px;margin-bottom:18px;"><tbody>';
        self::riga(__('Pratica', 'formedil'), $token);
        self::riga(__('Destinatario', 'formedil'), $destinatario);
        self::riga(__('Esito scelto', 'formedil'), $etichette[$esito]);
        self::riga(__('Nuovo stato', 'formedil'), self::statoLabel(self::statoPerEsito($esito)));
        echo '</tbody></table>';

        // Il corpo dell'email è HTML completo: va isolato per non interferire
        // con lo stile di wp-admin.
        echo '<h2>' . esc_html__('Messaggio', 'formedil') . '</h2>';
        echo '<iframe title="' . esc_attr__('Anteprima email', 'formedil') . '" style="width:100%;max-width:760px;height:620px;border:1px solid #c3c4c7;background:#fff;" srcdoc="'
            . esc_attr(Mailer::anteprimaRiscontro($dati, $token, $esito, $indicazioni))
            . '"></iframe>';

        echo '<p style="margin-top:20px;">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:10px;">';
        echo '<input type="hidden" name="action" value="formedil_riscontro" />';
        echo '<input type="hidden" name="token" value="' . esc_attr($token) . '" />';
        echo '<input type="hidden" name="esito" value="' . esc_attr($esito) . '" />';
        echo '<input type="hidden" name="indicazioni" value="' . esc_attr($indicazioni) . '" />';
        wp_nonce_field('formedil_riscontro_invio_' . $token);
        echo '<button type="submit" class="button button-primary">' . esc_html__('Invia il riscontro', 'formedil') . '</button>';
        echo '</form>';
        echo '<a class="button" href="' . esc_url(self::detailUrl($token)) . '">' . esc_html__('Torna indietro', 'formedil') . '</a>';
        echo '</p>';

        echo '</div>';
    }

    /**
     * Invio definitivo: manda l'email, aggiorna lo stato e registra l'evento.
     * Se l'email non parte lo stato non viene toccato, così l'operatore può
     * riprovare senza che la pratica risulti chiusa senza comunicazione.
     */
    public function handleRiscontro(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }

        $token = isset($_POST['token']) ? Token::normalize(sanitize_text_field(wp_unslash($_POST['token']))) : '';
        check_admin_referer('formedil_riscontro_invio_' . $token);

        $esito = isset($_POST['esito']) ? sanitize_text_field(wp_unslash($_POST['esito'])) : '';
        $indicazioni = isset($_POST['indicazioni']) ? sanitize_textarea_field(wp_unslash($_POST['indicazioni'])) : '';

        $row = Repository::findByToken($token);
        $detail = self::detailUrl($token);
        $nuovo = self::statoPerEsito($esito);

        if ($row === null || $nuovo === '' || !Status::canTransition((string) ($row['stato'] ?? ''), $nuovo)) {
            wp_safe_redirect(add_query_arg('error', '1', $detail));
            exit;
        }

        if ($esito !== Mailer::ESITO_ACCETTATA && trim($indicazioni) === '') {
            wp_safe_redirect(add_query_arg('error', '1', $detail));
            exit;
        }

        $dati = (array) ($row['dati'] ?? []);
        if (!Mailer::riscontro($dati, $token, $esito, $indicazioni)) {
            wp_safe_redirect(add_query_arg('mail_error', '1', $detail));
            exit;
        }

        $precedente = (string) ($row['stato'] ?? '');
        Repository::updateStato($token, $nuovo);

        $etichette = Mailer::esiti();
        $dettaglio = ($etichette[$esito] ?? $esito) . ' · ' . self::statoLabel($precedente) . ' → ' . self::statoLabel($nuovo);
        if (trim($indicazioni) !== '') {
            $dettaglio .= ' · ' . $indicazioni;
        }

        Audit::record((int) ($row['id'] ?? 0), $token, Audit::STATO_CAMBIATO, $dettaglio);

        wp_safe_redirect(add_query_arg('riscontro', '1', $detail));
        exit;
    }

    /** Stato in cui finisce la pratica per ciascun esito. */
    private static function statoPerEsito(string $esito): string
    {
        switch ($esito) {
            case Mailer::ESITO_ACCETTATA:
            case Mailer::ESITO_CON_INDICAZIONI:
                return Status::APPROVATA;
            case Mailer::ESITO_NON_ACCOLTA:
                return Status::RESPINTA;
            default:
                return '';
        }
    }

    /**
     * Email del richiedente, con gli stessi fallback usati dal Mailer.
     *
     * @param array<string,mixed> $dati
     */
    private static function emailRichiedente(array $dati): string
    {
        $to = trim((string) ($dati['azienda_email'] ?? ''));
        if ($to === '') {
            $to = trim((string) ($dati['org_email'] ?? ''));
        }
        if ($to === '') {
            foreach ((array) ($dati['imprese'] ?? []) as $im) {
                if (is_array($im) && trim((string) ($im['azienda_email'] ?? '')) !== '') {
                    $to = trim((string) $im['azienda_email']);
                    break;
                }
            }
        }
        return $to;
    }

    /** URL del dettaglio della pratica. */
    private static function detailUrl(string $token): string
    {
        return admin_url('admin.php?page=' . self::SLUG . '&token=' . rawurlencode($token));
    }

    // ----------------------------------------------------------- AZIONI POST

    public function handleStato(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }

        $token = isset($_POST['token']) ? Token::normalize(sanitize_text_field(wp_unslash($_POST['token']))) : '';
        check_admin_referer('formedil_stato_' . $token);

        $nuovo = isset($_POST['stato']) ? sanitize_text_field(wp_unslash($_POST['stato'])) : '';
        $row = Repository::findByToken($token);

        $detail = admin_url('admin.php?page=' . self::SLUG . '&token=' . rawurlencode($token));

        if ($row === null || !Status::canTransition((string) ($row['stato'] ?? ''), $nuovo)) {
            wp_safe_redirect(add_query_arg('error', '1', $detail));
            exit;
        }

        $precedente = (string) ($row['stato'] ?? '');
        Repository::updateStato($token, $nuovo);
        Audit::record(
            (int) ($row['id'] ?? 0),
            $token,
            Audit::STATO_CAMBIATO,
            self::statoLabel($precedente) . ' → ' . self::statoLabel($nuovo)
        );
        wp_safe_redirect(add_query_arg('updated', '1', $detail));
        exit;
    }

    public function handleDownload(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('formedil_download_' . $id);

        $allegato = Repository::findAllegato($id);
        if ($allegato === null) {
            wp_die(esc_html__('Allegato non trovato.', 'formedil'));
        }

        $richiesta = Repository::findById((int) ($allegato['richiesta_id'] ?? 0));
        if ($richiesta === null) {
            wp_die(esc_html__('Richiesta non trovata.', 'formedil'));
        }

        $path = AllegatoStorage::path((string) $richiesta['token'], (string) $allegato['filename']);
        if (!is_file($path)) {
            wp_die(esc_html__('File mancante.', 'formedil'));
        }

        nocache_headers();
        header('Content-Type: ' . (string) ($allegato['mime'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . sanitize_file_name((string) ($allegato['original_name'] ?? basename($path))) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Scarica il PDF generato alla creazione (modulo non firmato), keyed by token.
     */
    public function handleDownloadPdf(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }

        $token = isset($_GET['token']) ? Token::normalize(sanitize_text_field(wp_unslash($_GET['token']))) : '';
        check_admin_referer('formedil_download_pdf_' . $token);

        if ($token === '' || Repository::findByToken($token) === null) {
            wp_die(esc_html__('Richiesta non trovata.', 'formedil'));
        }

        $filename = 'richiesta-' . $token . '.pdf';
        $path = PdfGenerator::path($filename);
        if (!is_file($path)) {
            wp_die(esc_html__('File mancante.', 'formedil'));
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    // --------------------------------------------------------------- HELPER

    private static function riga(string $label, string $value): void
    {
        echo '<tr><th scope="row" style="width:160px;">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
    }

    private static function statoLabel(string $stato): string
    {
        $map = [
            Status::GENERATA         => 'Generata',
            Status::FIRMATA_CARICATA => 'Firmata e caricata',
            Status::IN_VERIFICA      => 'In verifica',
            Status::APPROVATA        => 'Approvata',
            Status::RESPINTA         => 'Respinta',
        ];
        return $map[$stato] ?? $stato;
    }

    private static function statoTag(string $stato): string
    {
        $colors = [
            Status::GENERATA         => '#64748b',
            Status::FIRMATA_CARICATA => '#1d4ed8',
            Status::IN_VERIFICA      => '#b45309',
            Status::APPROVATA        => '#15803d',
            Status::RESPINTA         => '#b91c1c',
        ];
        $bg = $colors[$stato] ?? '#64748b';
        return '<span style="display:inline-block;padding:2px 10px;border-radius:999px;background:' . esc_attr($bg) . ';color:#fff;font-size:12px;font-weight:600;">' . esc_html(self::statoLabel($stato)) . '</span>';
    }

    private static function azioneLabel(string $stato): string
    {
        $map = [
            Status::IN_VERIFICA => 'Metti in verifica',
            Status::APPROVATA   => 'Approva',
            Status::RESPINTA    => 'Respingi',
        ];
        return $map[$stato] ?? ('→ ' . self::statoLabel($stato));
    }

    private static function periodo(string $dal, string $al): string
    {
        if ($dal === '' && $al === '') {
            return '—';
        }
        return self::formatData($dal) . ' – ' . self::formatData($al);
    }

    private static function formatData(string $iso): string
    {
        if ($iso === '') {
            return '—';
        }
        $ts = strtotime($iso);
        return $ts ? date_i18n('d/m/Y', $ts) : $iso;
    }

    private static function formatDataOra(string $iso): string
    {
        if ($iso === '') {
            return '—';
        }
        $ts = strtotime($iso . ' UTC');
        return $ts ? date_i18n('d/m/Y H:i', $ts) : $iso;
    }

    private static function eventoLabel(string $evento): string
    {
        $map = [
            Audit::RICHIESTA_CREATA => 'Richiesta creata',
            Audit::INVIO_RICEVUTO   => 'Documenti ricevuti',
            Audit::STATO_CAMBIATO   => 'Stato modificato',
        ];
        return $map[$evento] ?? $evento;
    }

    private static function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }
        $kb = $bytes / 1024;
        return $kb < 1024 ? round($kb) . ' KB' : round($kb / 1024, 1) . ' MB';
    }
}
