<?php

declare(strict_types=1);

namespace Formedil\Moduli\Service;

use Formedil\Moduli\Data\Repository;
use Formedil\Moduli\Storage\AllegatoStorage;
use Formedil\Moduli\Support\Audit;
use Formedil\Moduli\Support\Status;

/**
 * Orchestrazione della fase di invio (S3):
 * valida token e stato -> valida i file -> salva su disco e DB -> stato FIRMATA_CARICATA.
 * Tiene il controller REST sottile e la logica testabile.
 */
final class InvioService
{
    /**
     * @param array<string,mixed> $firmato file $_FILES del PDF firmato (obbligatorio)
     * @param array<int,array<string,mixed>> $allegati lista normalizzata di file (opzionali)
     * @return array{ok:bool, code?:string, message?:string, errors?:array<string,string>, stato?:string, allegati?:int}
     */
    public function invia(string $token, array $firmato, array $allegati): array
    {
        $row = Repository::findByToken($token);
        if ($row === null) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'Nessuna richiesta trovata per questo codice.'];
        }

        // Guardia di stato: si carica una volta sola, solo da GENERATA.
        if (($row['stato'] ?? '') !== Status::GENERATA) {
            return [
                'ok'      => false,
                'code'    => 'conflict',
                'message' => 'I documenti per questa richiesta risultano già inviati.',
                'stato'   => (string) ($row['stato'] ?? ''),
            ];
        }

        // Validazione: PDF firmato obbligatorio.
        $errFirmato = AllegatoStorage::validate($firmato, AllegatoStorage::MIME_FIRMATO);
        if ($errFirmato !== '') {
            return ['ok' => false, 'code' => 'validation_failed', 'errors' => ['firmato' => $errFirmato]];
        }

        // Cap d'insieme (numero allegati + dimensione totale) prima del disco.
        $errCollezione = AllegatoStorage::validateCollection($firmato, $allegati);
        if ($errCollezione !== '') {
            return ['ok' => false, 'code' => 'validation_failed', 'errors' => ['allegati' => $errCollezione]];
        }

        // Validazione allegati liberi (se presenti) prima di scrivere su disco.
        $errors = [];
        foreach ($allegati as $i => $file) {
            $err = AllegatoStorage::validate($file, AllegatoStorage::MIME_ALLEGATO);
            if ($err !== '') {
                $errors['allegati_' . $i] = $err;
            }
        }
        if ($errors !== []) {
            return ['ok' => false, 'code' => 'validation_failed', 'errors' => $errors];
        }

        $richiestaId = (int) ($row['id'] ?? 0);

        // Salvataggio: prima il firmato, poi gli allegati.
        try {
            $stored = AllegatoStorage::store($token, $firmato, 'FIRMATO');
            Repository::insertAllegato($richiestaId, $stored + ['tipo' => 'FIRMATO']);

            $count = 1;
            foreach ($allegati as $file) {
                $a = AllegatoStorage::store($token, $file, 'ALLEGATO');
                Repository::insertAllegato($richiestaId, $a + ['tipo' => 'ALLEGATO']);
                $count++;
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'code' => 'storage_error', 'message' => 'Salvataggio file fallito: ' . $e->getMessage()];
        }

        Repository::updateStato($token, Status::FIRMATA_CARICATA);
        Audit::record($richiestaId, $token, Audit::INVIO_RICEVUTO, $count . ' file caricati');

        // Conferma "documenti firmati ricevuti" al richiedente (non bloccante).
        $dati = is_array($row['dati'] ?? null) ? $row['dati'] : [];
        Mailer::documentiRicevuti($dati, $token);

        return [
            'ok'        => true,
            'stato'     => Status::FIRMATA_CARICATA,
            'allegati'  => $count,
        ];
    }
}
