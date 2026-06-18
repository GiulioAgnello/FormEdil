<?php

declare(strict_types=1);

namespace Formedil\Moduli\Admin;

use Formedil\Moduli\Data\Repository;
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
    public const SLUG = 'formedil-richieste';
    public const CAP = 'manage_options';
    private const PER_PAGE = 20;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_formedil_stato', [$this, 'handleStato']);
        add_action('admin_post_formedil_download', [$this, 'handleDownload']);
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
