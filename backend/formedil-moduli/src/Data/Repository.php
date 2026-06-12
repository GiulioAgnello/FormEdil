<?php

declare(strict_types=1);

namespace Formedil\Moduli\Data;

use Formedil\Moduli\Support\Status;

/**
 * Accesso alla tabella delle richieste.
 * Unico punto in cui si parla con il database: tutte le query passano da qui.
 */
final class Repository
{
    private const TABLE = 'formedil_richieste';

    private static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    /** Crea o aggiorna lo schema della tabella (idempotente, via dbDelta). */
    public static function createTable(): void
    {
        global $wpdb;
        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL,
            variante VARCHAR(8) NOT NULL,
            stato VARCHAR(32) NOT NULL DEFAULT 'GENERATA',
            dati LONGTEXT NOT NULL,
            pdf_filename VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY stato (stato)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Inserisce una nuova richiesta.
     *
     * @param array<string,mixed> $dati
     * @return int|false ID inserito o false in caso di errore.
     */
    public static function insert(string $token, string $variante, array $dati)
    {
        global $wpdb;
        $now = gmdate('Y-m-d H:i:s');

        $ok = $wpdb->insert(
            self::table(),
            [
                'token'      => $token,
                'variante'   => $variante,
                'stato'      => Status::GENERATA,
                'dati'       => wp_json_encode($dati),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $ok ? (int) $wpdb->insert_id : false;
    }

    /** Salva il nome del file PDF generato per una richiesta. */
    public static function setPdfFilename(int $id, string $filename): void
    {
        global $wpdb;
        $wpdb->update(
            self::table(),
            ['pdf_filename' => $filename, 'updated_at' => gmdate('Y-m-d H:i:s')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Trova una richiesta dal token.
     *
     * @return array<string,mixed>|null
     */
    public static function findByToken(string $token): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE token = %s', $token),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $row['dati'] = json_decode((string) $row['dati'], true) ?: [];
        return $row;
    }

    public static function updateStato(string $token, string $stato): bool
    {
        global $wpdb;
        $res = $wpdb->update(
            self::table(),
            ['stato' => $stato, 'updated_at' => gmdate('Y-m-d H:i:s')],
            ['token' => $token],
            ['%s', '%s'],
            ['%s']
        );
        return $res !== false;
    }
}
