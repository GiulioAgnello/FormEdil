<?php

declare(strict_types=1);

namespace Formedil\Moduli\Support;

/**
 * Rate limiting per gli endpoint pubblici (S8).
 *
 * Conteggio a finestra fissa per coppia bucket+IP, salvato in un transient
 * WordPress (in cache object se presente, altrimenti su opzioni). Niente
 * dipendenze esterne: serve a frenare abusi e tentativi di enumerazione del
 * token, non a fare sicurezza crittografica.
 *
 * I limiti di default sono sovrascrivibili col filtro `formedil_rate_limit`:
 *   add_filter('formedil_rate_limit', function ($conf, $bucket) {
 *       if ($bucket === 'crea') { $conf['max'] = 5; }
 *       return $conf;
 *   }, 10, 2);
 * Impostare 'max' a 0 disattiva il limite per quel bucket.
 */
final class RateLimiter
{
    private const PREFIX = 'formedil_rl_';

    /**
     * Registra un tentativo e dice se è entro i limiti.
     *
     * @param string $bucket etichetta della rotta (es. 'crea', 'invio', 'lookup')
     * @param int    $max    tentativi massimi nella finestra
     * @param int    $window ampiezza della finestra in secondi
     * @return array{ok:bool, retry_after:int, remaining:int}
     */
    public static function check(string $bucket, int $max, int $window): array
    {
        [$max, $window] = self::limits($bucket, $max, $window);

        // max <= 0 => limite disattivato.
        if ($max <= 0 || $window <= 0) {
            return ['ok' => true, 'retry_after' => 0, 'remaining' => PHP_INT_MAX];
        }

        $key = self::PREFIX . $bucket . '_' . md5(self::clientIp());
        $now = time();

        $entry = get_transient($key);
        if (!is_array($entry) || (int) ($entry['reset'] ?? 0) <= $now) {
            $entry = ['count' => 0, 'reset' => $now + $window];
        }

        $entry['count'] = (int) $entry['count'] + 1;
        $ttl = max(1, (int) $entry['reset'] - $now);
        set_transient($key, $entry, $ttl);

        if ($entry['count'] > $max) {
            return ['ok' => false, 'retry_after' => $ttl, 'remaining' => 0];
        }

        return ['ok' => true, 'retry_after' => 0, 'remaining' => max(0, $max - $entry['count'])];
    }

    /** IP del client. Sovrascrivibile via filtro per setup dietro reverse proxy. */
    public static function clientIp(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        return (string) apply_filters('formedil_client_ip', $ip);
    }

    /**
     * Applica eventuali override di configurazione.
     *
     * @return array{0:int,1:int} [max, window]
     */
    private static function limits(string $bucket, int $max, int $window): array
    {
        $conf = apply_filters('formedil_rate_limit', ['max' => $max, 'window' => $window], $bucket);
        if (!is_array($conf)) {
            return [$max, $window];
        }
        return [(int) ($conf['max'] ?? $max), (int) ($conf['window'] ?? $window)];
    }
}
