<?php

declare(strict_types=1);

namespace Formedil\Moduli\Support;

/**
 * Stati del ciclo di vita di una richiesta.
 *
 * GENERATA          -> PDF creato, in attesa di firma e upload
 * FIRMATA_CARICATA  -> l'utente ha caricato PDF firmato + allegati
 * IN_VERIFICA       -> FORMEDIL sta verificando
 * APPROVATA         -> collaborazione accordata
 * RESPINTA          -> collaborazione rifiutata
 */
final class Status
{
    public const GENERATA = 'GENERATA';
    public const FIRMATA_CARICATA = 'FIRMATA_CARICATA';
    public const IN_VERIFICA = 'IN_VERIFICA';
    public const APPROVATA = 'APPROVATA';
    public const RESPINTA = 'RESPINTA';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::GENERATA,
            self::FIRMATA_CARICATA,
            self::IN_VERIFICA,
            self::APPROVATA,
            self::RESPINTA,
        ];
    }

    public static function isValid(string $stato): bool
    {
        return in_array($stato, self::all(), true);
    }
}
