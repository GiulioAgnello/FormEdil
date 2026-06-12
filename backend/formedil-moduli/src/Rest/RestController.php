<?php

declare(strict_types=1);

namespace Formedil\Moduli\Rest;

use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Pdf\PdfGenerator;
use Formedil\Moduli\Schema\SchemaProvider;
use Formedil\Moduli\Service\RichiestaService;
use Formedil\Moduli\Support\Token;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Rotte REST sotto /wp-json/formedil/v1/.
 *
 * S0:  GET  /health, GET /schema
 * S1:  POST /richieste                  crea richiesta + PDF + token
 *      GET  /richieste/{token}          riepilogo minimo per la pagina di invio
 *      GET  /richieste/{token}/pdf      download del PDF generato
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
                    'type'              => 'string',
                    'required'          => false,
                    'enum'              => ['DTL', 'ENTE'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NS, '/richieste', [
            'methods'             => 'POST',
            'callback'            => [$this, 'creaRichiesta'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NS, '/richieste/(?P<token>[A-Za-z0-9\-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getRichiesta'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NS, '/richieste/(?P<token>[A-Za-z0-9\-]+)/pdf', [
            'methods'             => 'GET',
            'callback'            => [$this, 'downloadPdf'],
            'permission_callback' => '__return_true',
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
            return new WP_REST_Response(['error' => 'schema_not_found'], 500);
        }
        return new WP_REST_Response($schema, 200);
    }

    public function creaRichiesta(WP_REST_Request $request): WP_REST_Response
    {
        $body = $request->get_json_params();
        $variante = isset($body['variante']) ? sanitize_text_field((string) $body['variante']) : '';
        $dati = isset($body['dati']) && is_array($body['dati']) ? $body['dati'] : [];

        if (!SchemaProvider::isValidVariant($variante)) {
            return new WP_REST_Response([
                'error'   => 'invalid_variant',
                'message' => 'Variante non valida. Usare DTL o ENTE.',
            ], 400);
        }

        $service = new RichiestaService();
        $result = $service->crea($variante, $dati, $this->frontendBaseUrl());

        if (!($result['ok'] ?? false)) {
            $status = isset($result['errors']) ? 422 : 400;
            return new WP_REST_Response([
                'error'   => 'validation_failed',
                'message' => $result['message'] ?? 'Errore.',
                'errors'  => $result['errors'] ?? null,
            ], $status);
        }

        $token = $result['token'];
        $base = trailingslashit($this->frontendBaseUrl());

        return new WP_REST_Response([
            'ok'        => true,
            'token'     => $token,
            'pdf_url'   => rest_url(self::NS . '/richieste/' . $token . '/pdf'),
            'invio_url' => $base . 'invio/' . $token,
        ], 201);
    }

    public function getRichiesta(WP_REST_Request $request): WP_REST_Response
    {
        $token = Token::normalize((string) $request->get_param('token'));
        $row = Repository::findByToken($token);

        if ($row === null) {
            return new WP_REST_Response([
                'error'   => 'not_found',
                'message' => 'Nessuna richiesta trovata per questo codice.',
            ], 404);
        }

        $service = new RichiestaService();
        return new WP_REST_Response($service->riepilogo($row), 200);
    }

    public function downloadPdf(WP_REST_Request $request)
    {
        $token = Token::normalize((string) $request->get_param('token'));
        $row = Repository::findByToken($token);

        if ($row === null || empty($row['pdf_filename'])) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }

        $path = PdfGenerator::path((string) $row['pdf_filename']);
        if (!is_file($path)) {
            return new WP_REST_Response(['error' => 'file_missing'], 404);
        }

        // Stream del PDF (esce dal ciclo REST standard per inviare il binario).
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    /** Base URL del frontend SPA (per i link di invio). Configurabile via filtro. */
    private function frontendBaseUrl(): string
    {
        $default = 'https://moduli.formedillecce.it';
        return (string) apply_filters('formedil_frontend_base_url', $default);
    }
}
