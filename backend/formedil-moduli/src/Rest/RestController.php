<?php

declare(strict_types=1);

namespace Formedil\Moduli\Rest;

use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Pdf\PdfGenerator;
use Formedil\Moduli\Schema\SchemaProvider;
use Formedil\Moduli\Service\InvioService;
use Formedil\Moduli\Service\RichiestaService;
use Formedil\Moduli\Support\RateLimiter;
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

        // S3: upload del PDF firmato + allegati (multipart/form-data).
        register_rest_route(self::NS, '/richieste/(?P<token>[A-Za-z0-9\-]+)/invio', [
            'methods'             => 'POST',
            'callback'            => [$this, 'inviaDocumentazione'],
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
        // Anti-abuso: max ~10 creazioni per IP ogni 10 minuti.
        if ($limited = $this->rateLimit('crea', 10, 600)) {
            return $limited;
        }

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
        // Anti-enumerazione: max ~30 lookup per IP ogni 5 minuti.
        if ($limited = $this->rateLimit('lookup', 30, 300)) {
            return $limited;
        }

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

    /**
     * S3: riceve il PDF firmato (campo "firmato") e gli allegati liberi
     * (campo "allegati[]") in multipart/form-data, cambia lo stato a FIRMATA_CARICATA.
     */
    public function inviaDocumentazione(WP_REST_Request $request): WP_REST_Response
    {
        // Anti-abuso: max ~20 invii per IP ogni 10 minuti.
        if ($limited = $this->rateLimit('invio', 20, 600)) {
            return $limited;
        }

        $token = Token::normalize((string) $request->get_param('token'));
        $files = $request->get_file_params();

        $firmato = isset($files['firmato']) && is_array($files['firmato']) ? $files['firmato'] : [];
        $allegati = isset($files['allegati']) ? self::normalizeFiles($files['allegati']) : [];

        $service = new InvioService();
        $result = $service->invia($token, $firmato, $allegati);

        if (!($result['ok'] ?? false)) {
            $statusMap = [
                'not_found'         => 404,
                'conflict'          => 409,
                'validation_failed' => 422,
                'storage_error'     => 500,
            ];
            $code = (string) ($result['code'] ?? 'error');
            return new WP_REST_Response([
                'error'   => $code,
                'message' => $result['message'] ?? 'Invio non riuscito.',
                'errors'  => $result['errors'] ?? null,
                'stato'   => $result['stato'] ?? null,
            ], $statusMap[$code] ?? 400);
        }

        return new WP_REST_Response([
            'ok'       => true,
            'stato'    => $result['stato'],
            'allegati' => $result['allegati'],
            'message'  => 'Documenti ricevuti correttamente.',
        ], 200);
    }

    /**
     * Normalizza il campo file multiplo ($_FILES['allegati']) — che PHP
     * struttura per chiave e non per indice — in una lista di file singoli.
     *
     * @param array<string,mixed> $field
     * @return array<int,array<string,mixed>>
     */
    private static function normalizeFiles(array $field): array
    {
        if (!isset($field['name'])) {
            return [];
        }

        // Campo singolo (non array): un solo file.
        if (!is_array($field['name'])) {
            return ((int) ($field['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$field];
        }

        $out = [];
        foreach (array_keys($field['name']) as $i) {
            if ((int) ($field['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $field['name'][$i] ?? '',
                'type'     => $field['type'][$i] ?? '',
                'tmp_name' => $field['tmp_name'][$i] ?? '',
                'error'    => $field['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $field['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Applica il rate limit a una rotta pubblica.
     * Ritorna una risposta 429 (con Retry-After) se il limite è superato,
     * altrimenti null per proseguire.
     */
    private function rateLimit(string $bucket, int $max, int $window): ?WP_REST_Response
    {
        $r = RateLimiter::check($bucket, $max, $window);
        if ($r['ok']) {
            return null;
        }
        $resp = new WP_REST_Response([
            'error'   => 'rate_limited',
            'message' => 'Troppe richieste dallo stesso indirizzo. Riprova tra qualche minuto.',
        ], 429);
        $resp->header('Retry-After', (string) $r['retry_after']);
        return $resp;
    }

    /** Base URL del frontend SPA (per i link di invio). Configurabile via filtro. */
    private function frontendBaseUrl(): string
    {
        $default = 'https://moduli.formedillecce.it';
        return (string) apply_filters('formedil_frontend_base_url', $default);
    }
}
