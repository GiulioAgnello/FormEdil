<?php

declare(strict_types=1);

namespace Formedil\Moduli\Core;

use Formedil\Moduli\Admin\Panel;
use Formedil\Moduli\Admin\SettingsPage;
use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Rest\RestController;

/**
 * Classe principale: collega gli hook di WordPress.
 *
 * Mantiene il bootstrap snello. Ogni responsabilità (REST pubblica, gestionale
 * wp-admin, ...) vive in una classe dedicata e viene registrata qui.
 */
final class Plugin
{
    public function register(): void
    {
        // Allinea lo schema del DB se necessario (es. dopo un deploy che
        // aggiunge colonne): così le installazioni già attive si aggiornano
        // da sole, senza dover disattivare/riattivare il plugin.
        Repository::ensureSchema();

        // API REST pubbliche (creazione richiesta, invio documenti).
        $rest = new RestController();
        add_action('rest_api_init', [$rest, 'registerRoutes']);

        // Gestionale dentro wp-admin (autenticazione e permessi di WordPress).
        $panel = new Panel();
        $panel->register();

        // Impostazioni email: sottomenu del gestionale. Registra anche i filtri
        // che alimentano il Mailer, quindi va agganciata sempre, non solo in
        // wp-admin: le notifiche partono anche da richieste fatte dal sito.
        $settings = new SettingsPage();
        $settings->register();
    }
}
