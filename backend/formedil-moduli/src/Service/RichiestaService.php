<?php

declare(strict_types=1);

namespace Formedil\Moduli\Service;

use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Pdf\PdfGenerator;
use Formedil\Moduli\Support\Token;
use Formedil\Moduli\Validation\Validator;

/**
 * Orchestrazione della creazione di una richiesta:
 * valida -> genera token -> salva -> genera PDF.
 * Tiene il controller REST sottile e la logica testabile.
 */
final class RichiestaService
{
    /**
     * @param array<string,mixed> $dati
     * @return array{ok:bool, token?:string, errors?:array<string,string>, message?:string}
     */
    public function crea(string $variante, array $dati, string $frontendBaseUrl): array
    {
        $validator = new Validator();
        $errors = $validator->validate($variante, $dati);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Dati non validi.'];
        }

        // Token univoco (riprova in caso di collisione, evento rarissimo).
        $token = $this->generaTokenUnico();

        $id = Repository::insert($token, $variante, $dati);
        if ($id === false) {
            return ['ok' => false, 'message' => 'Impossibile salvare la richiesta.'];
        }

        $invioUrl = trailingslashit($frontendBaseUrl) . 'invio/' . $token;

        try {
            $filename = PdfGenerator::generate($variante, $dati, $token, $invioUrl);
            Repository::setPdfFilename($id, $filename);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Richiesta salvata ma generazione PDF fallita: ' . $e->getMessage()];
        }

        return ['ok' => true, 'token' => $token];
    }

    /**
     * Riepilogo minimo e sicuro di una richiesta, per la pagina di invio.
     * Non espone dati personali dei partecipanti.
     *
     * @param array<string,mixed> $row riga dal Repository (con 'dati' decodificato)
     * @return array<string,mixed>
     */
    public function riepilogo(array $row): array
    {
        $dati = is_array($row['dati'] ?? null) ? $row['dati'] : [];

        $denominazione = (string) ($dati['azienda_ragione_sociale'] ?? '');
        $tipo = $dati['tipo_corso'] ?? '';
        $tipiCorso = (is_string($tipo) && $tipo !== '') ? [$tipo] : [];

        return [
            'token'        => $row['token'] ?? '',
            'variante'     => $row['variante'] ?? '',
            'stato'        => $row['stato'] ?? '',
            'denominazione'=> $denominazione,
            'tipi_corso'   => $tipiCorso,
            'durata_dal'   => $dati['durata_dal'] ?? '',
            'durata_al'    => $dati['durata_al'] ?? '',
            'created_at'   => $row['created_at'] ?? '',
        ];
    }

    /**
     * Dettaglio completo per il pannello admin: tutti i dati + allegati e
     * transizioni di stato consentite.
     *
     * @param array<string,mixed> $row riga dal Repository (con 'dati' decodificato)
     * @param array<int,array<string,mixed>> $allegati righe allegati
     * @return array<string,mixed>
     */
    public function dettaglio(array $row, array $allegati): array
    {
        $base = $this->riepilogo($row);

        $base['dati'] = is_array($row['dati'] ?? null) ? $row['dati'] : [];
        $base['updated_at'] = $row['updated_at'] ?? '';
        $base['transizioni'] = \Formedil\Moduli\Support\Status::transitions()[$row['stato'] ?? ''] ?? [];
        $base['allegati'] = array_map(static function (array $a): array {
            return [
                'id'            => (int) ($a['id'] ?? 0),
                'tipo'          => $a['tipo'] ?? '',
                'original_name' => $a['original_name'] ?? '',
                'mime'          => $a['mime'] ?? '',
                'size'          => (int) ($a['size'] ?? 0),
                'created_at'    => $a['created_at'] ?? '',
            ];
        }, $allegati);

        return $base;
    }

    private function generaTokenUnico(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $token = Token::generate();
            if (Repository::findByToken($token) === null) {
                return $token;
            }
        }
        // Estremamente improbabile: aggiunge entropia temporale.
        return Token::generate();
    }
}
