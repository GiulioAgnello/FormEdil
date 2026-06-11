<?php

declare(strict_types=1);

namespace Formedil\Moduli\Schema;

/**
 * Carica e fornisce lo schema canonico dei moduli.
 *
 * Lo schema è un singolo file JSON condiviso con il frontend: è l'unica fonte
 * di verità per campi, varianti e validazioni. Sia il wizard React sia la
 * generazione PDF (sprint successivi) leggono da qui.
 */
final class SchemaProvider
{
    /** @var array<string,mixed>|null Cache in-memory dello schema decodificato. */
    private static ?array $cache = null;

    /**
     * Restituisce lo schema come array associativo.
     *
     * @return array<string,mixed>
     */
    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = defined('FORMEDIL_SCHEMA_PATH') ? FORMEDIL_SCHEMA_PATH : '';

        if ($path === '' || !is_file($path)) {
            self::$cache = [];
            return self::$cache;
        }

        $raw = file_get_contents($path);
        $data = $raw !== false ? json_decode($raw, true) : null;

        self::$cache = is_array($data) ? $data : [];
        return self::$cache;
    }

    /**
     * Verifica che una variante (DTL/ENTE) esista nello schema.
     */
    public static function isValidVariant(string $variant): bool
    {
        $schema = self::get();
        return isset($schema['variants'][$variant]);
    }
}
