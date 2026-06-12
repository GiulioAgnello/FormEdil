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
        $isEnte = ($row['variante'] ?? '') === 'ENTE';

        $denominazione = $isEnte
            ? ($dati['ente_nome'] ?? $dati['impresa_nome'] ?? '')
            : ($dati['azienda_nome'] ?? '');

        return [
            'token'        => $row['token'] ?? '',
            'variante'     => $row['variante'] ?? '',
            'stato'        => $row['stato'] ?? '',
            'denominazione'=> $denominazione,
            'tipi_corso'   => is_array($dati['tipi_corso'] ?? null) ? $dati['tipi_corso'] : [],
            'durata_dal'   => $dati['durata_dal'] ?? '',
            'durata_al'    => $dati['durata_al'] ?? '',
            'created_at'   => $row['created_at'] ?? '',
        ];
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
