<?php

declare(strict_types=1);

namespace Formedil\Moduli\Admin;

use Formedil\Moduli\Service\Mailer;

/**
 * Pagina "Impostazioni email" sotto il menu FORMEDIL (S14).
 *
 * Permette alla segreteria di cambiare mittente e destinatari interni senza
 * toccare file sul server. I valori finiscono in una option di WordPress e
 * vengono letti dal Mailer attraverso i filtri già esistenti.
 *
 * Precedenza: se una costante è definita nel mu-plugin di configurazione
 * (formedil-config.php) quella vince, e qui il campo appare in sola lettura.
 * In questo modo esiste sempre una sola fonte di verità: il file, quando c'è.
 */
final class SettingsPage
{
    public const OPTION = 'formedil_email_settings';
    private const GROUP = 'formedil_email';
    private const SLUG = 'formedil-email';

    /** Valori di partenza quando l'opzione non è mai stata salvata. */
    private const DEFAULTS = [
        'from'      => '',
        'from_name' => 'FORMEDIL Lecce',
        'admin'     => '',
        'allegati'  => 1,
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_action('admin_post_formedil_email_test', [$this, 'handleTest']);

        // I filtri del Mailer leggono da qui. Priorità 5: più bassa del default
        // di WordPress (10), così il mu-plugin — che usa la priorità standard —
        // interviene dopo e ha l'ultima parola.
        add_filter('formedil_mail_from', [$this, 'filterFrom'], 5);
        add_filter('formedil_mail_from_name', [$this, 'filterFromName'], 5);
        add_filter('formedil_admin_email', [$this, 'filterAdmin'], 5);
        add_filter('formedil_mail_max_attach', [$this, 'filterMaxAttach'], 5);
    }

    // -----------------------------------------------------------------------
    // Lettura dei valori
    // -----------------------------------------------------------------------

    /** @return array<string,mixed> */
    public static function get(): array
    {
        $saved = get_option(self::OPTION, []);
        return array_merge(self::DEFAULTS, is_array($saved) ? $saved : []);
    }

    /**
     * Una costante del mu-plugin, se valorizzata, sovrascrive il pannello.
     * Restituisce stringa vuota quando non è definita o è lasciata in bianco.
     */
    private static function costante(string $nome): string
    {
        return defined($nome) ? trim((string) constant($nome)) : '';
    }

    public function filterFrom(string $default): string
    {
        $v = (string) self::get()['from'];
        return $v !== '' ? $v : $default;
    }

    public function filterFromName(string $default): string
    {
        $v = (string) self::get()['from_name'];
        return $v !== '' ? $v : $default;
    }

    /** @param string|array<int,string> $default */
    public function filterAdmin($default)
    {
        $v = (string) self::get()['admin'];
        return $v !== '' ? $v : $default;
    }

    public function filterMaxAttach(int $default): int
    {
        // Interruttore "allega i documenti": se spento, tetto a zero byte, così
        // resta solo il PDF firmato (che entra sempre) e nient'altro.
        return ((int) self::get()['allegati']) === 1 ? $default : 0;
    }

    // -----------------------------------------------------------------------
    // Menu e registrazione dei campi
    // -----------------------------------------------------------------------

    public function menu(): void
    {
        add_submenu_page(
            Panel::SLUG,
            'Impostazioni email',
            'Impostazioni email',
            Panel::CAP,
            self::SLUG,
            [$this, 'render']
        );
    }

    public function settings(): void
    {
        register_setting(self::GROUP, self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => self::DEFAULTS,
        ]);
    }

    /**
     * @param mixed $input
     * @return array<string,mixed>
     */
    public function sanitize($input): array
    {
        $in = is_array($input) ? $input : [];

        $from = sanitize_text_field((string) ($in['from'] ?? ''));
        if ($from !== '' && !is_email($from)) {
            add_settings_error(self::OPTION, 'from', 'Indirizzo mittente non valido: il campo è stato lasciato invariato.');
            $from = (string) self::get()['from'];
        }

        // I destinatari sono una lista separata da virgola: si validano uno per
        // uno e si segnalano quelli scartati, invece di rifiutare tutto.
        $adminRaw = sanitize_text_field((string) ($in['admin'] ?? ''));
        $validi = [];
        $scartati = [];
        foreach (explode(',', $adminRaw) as $pezzo) {
            $email = trim($pezzo);
            if ($email === '') {
                continue;
            }
            if (is_email($email)) {
                $validi[] = $email;
            } else {
                $scartati[] = $email;
            }
        }
        if ($scartati !== []) {
            add_settings_error(
                self::OPTION,
                'admin',
                'Indirizzi non validi ignorati: ' . esc_html(implode(', ', $scartati))
            );
        }

        return [
            'from'      => $from,
            'from_name' => sanitize_text_field((string) ($in['from_name'] ?? '')),
            'admin'     => implode(', ', $validi),
            'allegati'  => empty($in['allegati']) ? 0 : 1,
        ];
    }

    // -----------------------------------------------------------------------
    // Interfaccia
    // -----------------------------------------------------------------------

    public function render(): void
    {
        if (!current_user_can(Panel::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }

        $v = self::get();
        $cFrom = self::costante('FORMEDIL_MAIL_FROM');
        $cName = self::costante('FORMEDIL_MAIL_FROM_NAME');
        $cAdmin = self::costante('FORMEDIL_ADMIN_EMAIL');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Impostazioni email', 'formedil') . '</h1>';
        echo '<p class="description" style="max-width:640px;">'
            . esc_html__('Da qui si decidono mittente e destinatari delle notifiche automatiche. La password del server di posta non si imposta qui: resta nel plugin WP Mail SMTP.', 'formedil')
            . '</p>';

        settings_errors(self::OPTION);
        self::esitoTest();

        if ($cFrom !== '' || $cName !== '' || $cAdmin !== '') {
            echo '<div class="notice notice-info inline" style="margin:16px 0;padding:10px 14px;"><p>'
                . esc_html__('Alcuni valori sono impostati nel file di configurazione sul server (mu-plugins/formedil-config.php) e non sono modificabili da qui: il file ha la precedenza.', 'formedil')
                . '</p></div>';
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);

        echo '<table class="form-table" role="presentation"><tbody>';

        self::campoTesto(
            'from',
            __('Indirizzo mittente', 'formedil'),
            (string) $v['from'],
            $cFrom,
            __('Deve coincidere con l\'utenza configurata in WP Mail SMTP, altrimenti il server di posta rifiuta l\'invio.', 'formedil'),
            'no-reply@formedillecce.it'
        );

        self::campoTesto(
            'from_name',
            __('Nome mittente', 'formedil'),
            (string) $v['from_name'],
            $cName,
            __('Come compare il mittente nella casella di chi riceve.', 'formedil'),
            'FORMEDIL Lecce'
        );

        self::campoTesto(
            'admin',
            __('Destinatari interni', 'formedil'),
            (string) $v['admin'],
            $cAdmin,
            __('Più indirizzi separati da virgola. Ricevono la copia delle nuove richieste e la notifica con i documenti firmati.', 'formedil'),
            'direttore@formedillecce.it, segreteria@formedillecce.it'
        );

        // Interruttore allegati.
        echo '<tr><th scope="row">' . esc_html__('Documenti allegati', 'formedil') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPTION) . '[allegati]" value="1" '
            . checked(1, (int) $v['allegati'], false) . '> '
            . esc_html__('Allega i documenti caricati alla notifica interna', 'formedil') . '</label>';
        echo '<p class="description">'
            . esc_html__('Il PDF firmato viene allegato comunque. Se disattivi, gli altri file restano scaricabili solo dal pannello.', 'formedil')
            . '</p></td></tr>';

        echo '</tbody></table>';
        submit_button();
        echo '</form>';

        self::formTest();

        echo '</div>';
    }

    /**
     * Campo di testo che diventa in sola lettura quando il valore è imposto
     * dal file di configurazione.
     */
    private static function campoTesto(
        string $chiave,
        string $label,
        string $valore,
        string $costante,
        string $aiuto,
        string $placeholder
    ): void {
        $bloccato = $costante !== '';
        $mostrato = $bloccato ? $costante : $valore;

        echo '<tr><th scope="row"><label for="formedil-' . esc_attr($chiave) . '">' . esc_html($label) . '</label></th><td>';
        echo '<input type="text" class="regular-text" id="formedil-' . esc_attr($chiave) . '" '
            . 'name="' . esc_attr(self::OPTION) . '[' . esc_attr($chiave) . ']" '
            . 'value="' . esc_attr($mostrato) . '" '
            . 'placeholder="' . esc_attr($placeholder) . '" '
            . ($bloccato ? 'readonly disabled ' : '') . '>';

        if ($bloccato) {
            // Il campo disabilitato non viene inviato: si conserva il valore
            // salvato in modo che riattivando il file resti coerente.
            echo '<input type="hidden" name="' . esc_attr(self::OPTION) . '[' . esc_attr($chiave) . ']" value="' . esc_attr($valore) . '">';
            echo '<p class="description"><strong>' . esc_html__('Impostato dal file di configurazione.', 'formedil') . '</strong></p>';
        } else {
            echo '<p class="description">' . esc_html($aiuto) . '</p>';
        }

        echo '</td></tr>';
    }

    /** Messaggio di esito dopo un invio di prova. */
    private static function esitoTest(): void
    {
        $esito = isset($_GET['formedil_test']) ? sanitize_key(wp_unslash($_GET['formedil_test'])) : '';
        if ($esito === '') {
            return;
        }

        if ($esito === 'ok') {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Email di prova inviata. Controlla la casella, anche nella posta indesiderata.', 'formedil')
                . '</p></div>';
            return;
        }

        echo '<div class="notice notice-error is-dismissible"><p>'
            . esc_html__('Invio non riuscito. Verifica le impostazioni in WP Mail SMTP: di solito è la password della casella o il mittente che non coincide con l\'utenza SMTP.', 'formedil')
            . '</p></div>';
    }

    /** Riquadro per l'invio di un'email di prova. */
    private static function formTest(): void
    {
        $current = wp_get_current_user();
        $default = $current instanceof \WP_User ? (string) $current->user_email : '';

        echo '<hr><h2>' . esc_html__('Invio di prova', 'formedil') . '</h2>';
        echo '<p class="description">' . esc_html__('Manda un\'email di esempio con la grafica reale delle notifiche, per verificare che il server di posta funzioni.', 'formedil') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="formedil_email_test">';
        wp_nonce_field('formedil_email_test');
        echo '<input type="email" name="destinatario" class="regular-text" required value="' . esc_attr($default) . '"> ';
        submit_button(__('Invia email di prova', 'formedil'), 'secondary', 'submit', false);
        echo '</form>';
    }

    // -----------------------------------------------------------------------
    // Invio di prova
    // -----------------------------------------------------------------------

    public function handleTest(): void
    {
        if (!current_user_can(Panel::CAP)) {
            wp_die(esc_html__('Permesso negato.', 'formedil'));
        }
        check_admin_referer('formedil_email_test');

        $to = isset($_POST['destinatario']) ? sanitize_email(wp_unslash($_POST['destinatario'])) : '';
        $esito = $to !== '' && is_email($to) && Mailer::inviaProva($to) ? 'ok' : 'ko';

        wp_safe_redirect(add_query_arg(
            ['page' => self::SLUG, 'formedil_test' => $esito],
            admin_url('admin.php')
        ));
        exit;
    }
}
