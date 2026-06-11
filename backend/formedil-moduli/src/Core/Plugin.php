<?php

declare(strict_types=1);

namespace Formedil\Moduli\Core;

use Formedil\Moduli\Rest\RestController;

/**
 * Classe principale: collega gli hook di WordPress.
 *
 * Mantiene il bootstrap snello. Ogni responsabilità (REST, in futuro PDF,
 * storage, ecc.) vive in una classe dedicata e viene registrata qui.
 */
final class Plugin
{
    public function register(): void
    {
        // Registrazione delle rotte REST.
        $rest = new RestController();
        add_action('rest_api_init', [$rest, 'registerRoutes']);
    }
}
