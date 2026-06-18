<?php

declare(strict_types=1);

namespace Formedil\Moduli\Support;

use Formedil\Moduli\Data\Repository;

/**
 * Registro eventi (S8): traccia chi/quando ha fatto cosa su una richiesta.
 *
 * Pensato per tracciabilità e contestazioni, non per metriche. Scrive una riga
 * sulla tabella di audit tramite il Repository (unico punto di accesso al DB).
 * Non deve mai interrompere il flusso: gli errori di scrittura sono silenziati.
 */
final class Audit
{
    public const RICHIESTA_CREATA = 'RICHIESTA_CREATA';
    public const INVIO_RICEVUTO   = 'INVIO_RICEVUTO';
    public const STATO_CAMBIATO   = 'STATO_CAMBIATO';

    public static function record(int $richiestaId, string $token, string $evento, string $dettaglio = ''): void
    {
        try {
            Repository::insertAudit($richiestaId, $token, $evento, $dettaglio, self::attore(), RateLimiter::clientIp());
        } catch (\Throwable $e) {
            // L'audit non deve mai far fallire l'operazione principale.
        }
    }

    /** Utente loggato (per le azioni admin) o stringa vuota per le azioni pubbliche. */
    private static function attore(): string
    {
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if ($user && (int) $user->ID > 0) {
                return (string) $user->user_login;
            }
        }
        return '';
    }
}
