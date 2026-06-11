<?php

declare(strict_types=1);

namespace Formedil\Moduli\Rest;

use Formedil\Moduli\Schema\SchemaProvider;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registra le rotte REST sotto il namespace /wp-json/formedil/v1/.
 *
 * In S0 esponiamo solo:
 *   GET /health  -> stato del servizio
 *   GET /schema  -> schema canonico dei campi (usato dal frontend)
 *
 * Gli endpoint per creare richieste, generare PDF e gestire l'invio
 * arrivano negli sprint S1 e S3.
 */
final class RestController
{
    private const NS = FORMEDIL_REST_NAMESPACE;

    public function registerRoutes(): void
    {
        register_rest_route(self::NS, '/health', [
            'methods'             => 'GET',
            'callback'            => [$this, 'health'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NS, '/schema', [
            'methods'             => 'GET',
            'callback'            => [$this, 'schema'],
            'permission_callback' => '__return_true',
            'args'                => [
                'variante' => [
                    'description'       => 'Filtra lo schema per variante (DTL | ENTE).',
                    'type'              => 'string',
                    'required'          => false,
                    'enum'              => ['DTL', 'ENTE'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    public function health(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'ok'      => true,
            'service' => 'formedil-moduli',
            'version' => FORMEDIL_VERSION,
            'time'    => gmdate('c'),
        ], 200);
    }

    public function schema(WP_REST_Request $request): WP_REST_Response
    {
        $schema = SchemaProvider::get();

        if ($schema === []) {
            return new WP_REST_Response([
                'error'   => 'schema_not_found',
                'message' => 'Schema dei moduli non disponibile.',
            ], 500);
        }

        $variante = (string) $request->get_param('variante');
        if ($variante !== '' && !SchemaProvider::isValidVariant($variante)) {
            return new WP_REST_Response([
                'error'   => 'invalid_variant',
                'message' => 'Variante non valida. Usare DTL o ENTE.',
            ], 400);
        }

        // S0: restituiamo lo schema completo; il filtro per variante lato
        // server verrà rifinito in S2 insieme al renderer del wizard.
        return new WP_REST_Response($schema, 200);
    }
}
