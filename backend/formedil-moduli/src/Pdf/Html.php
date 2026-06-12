<?php

declare(strict_types=1);

namespace Formedil\Moduli\Pdf;

/**
 * Piccoli helper di rendering per il template PDF.
 * Tengono il template HTML pulito e leggibile.
 */
final class Html
{
    /** Escape sicuro per output HTML. */
    public static function esc($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /** Valore semplice o trattino se vuoto. */
    public static function val($value, string $empty = '—'): string
    {
        $v = is_scalar($value) ? trim((string) $value) : '';
        return $v === '' ? $empty : self::esc($v);
    }

    /** Data ISO (YYYY-MM-DD) -> gg/mm/aaaa. */
    public static function date($value): string
    {
        $v = is_string($value) ? trim($value) : '';
        if ($v === '') {
            return '__/__/____';
        }
        $ts = strtotime($v);
        return $ts ? date('d/m/Y', $ts) : self::esc($v);
    }

    /** Ora HH:MM -> invariata o placeholder. */
    public static function time($value): string
    {
        $v = is_string($value) ? trim($value) : '';
        return $v === '' ? '__:__' : self::esc($v);
    }

    /** Casella di spunta: piena se $checked. */
    public static function checkbox(bool $checked): string
    {
        return $checked ? '&#9745;' : '&#9744;'; // ☑ / ☐
    }

    /**
     * Risolve l'etichetta di un'opzione dato il suo value.
     *
     * @param array<int,array<string,mixed>> $options
     */
    public static function optionLabel(array $options, $value): string
    {
        foreach ($options as $opt) {
            if (($opt['value'] ?? null) === $value) {
                return self::esc($opt['label'] ?? $value);
            }
        }
        return self::esc((string) $value);
    }
}
