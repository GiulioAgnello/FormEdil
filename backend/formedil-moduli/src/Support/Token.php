<?php

declare(strict_types=1);

namespace Formedil\Moduli\Support;

/**
 * Generazione del token della richiesta.
 *
 * Formato: FME-XXXX-XXXX-XXXX (12 caratteri da un alfabeto senza simboli
 * ambigui). ~60 bit di entropia: non indovinabile, leggibile, scrivibile
 * a mano dall'utente. È la chiave d'accesso alla fase di invio.
 */
final class Token
{
    // Alfabeto Crockford-like: niente 0/O/1/I/L per evitare errori di lettura.
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const GROUPS = 3;
    private const GROUP_LEN = 4;
    public const PREFIX = 'FME';

    public static function generate(): string
    {
        $len = self::GROUPS * self::GROUP_LEN;
        $alphaLen = strlen(self::ALPHABET);
        $chars = '';
        for ($i = 0; $i < $len; $i++) {
            $chars .= self::ALPHABET[random_int(0, $alphaLen - 1)];
        }

        $parts = str_split($chars, self::GROUP_LEN);
        return self::PREFIX . '-' . implode('-', $parts);
    }

    /** Normalizza l'input utente (maiuscolo, trim) per il confronto. */
    public static function normalize(string $token): string
    {
        return strtoupper(trim($token));
    }

    public static function isWellFormed(string $token): bool
    {
        $pattern = '/^' . self::PREFIX . '(-[' . self::ALPHABET . ']{' . self::GROUP_LEN . '}){' . self::GROUPS . '}$/';
        return (bool) preg_match($pattern, self::normalize($token));
    }
}
