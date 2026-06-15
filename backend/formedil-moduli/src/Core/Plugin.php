<?php

declare(strict_types=1);

namespace Formedil\Moduli\Core;

use Formedil\Moduli\Admin\Panel;
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
        // API REST pubbliche (creazione richiesta, invio documenti).
        $rest = new RestController();
        add_action('rest_api_init', [$rest, 'registerRoutes']);

        // Gestionale dentro wp-admin (autenticazione e permessi di WordPress).
        $panel = new Panel();
        $panel->register();
    }
}
