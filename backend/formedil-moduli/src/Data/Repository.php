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
    private const TABLE_ALLEGATI = 'formedil_allegati';
    private const TABLE_AUDIT = 'formedil_audit';

    private static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    private static function tableAllegati(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_ALLEGATI;
    }

    private static function tableAudit(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_AUDIT;
    }

    /** Crea o aggiorna lo schema delle tabelle (idempotente, via dbDelta). */
    public static function createTable(): void
    {
        global $wpdb;
        $table = self::table();
        $allegati = self::tableAllegati();
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

        // Allegati caricati dall'utente in fase di invio (PDF firmato + extra).
        $sqlAllegati = "CREATE TABLE {$allegati} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            richiesta_id BIGINT UNSIGNED NOT NULL,
            tipo VARCHAR(16) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime VARCHAR(100) NOT NULL,
            size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY richiesta_id (richiesta_id)
        ) {$charset};";

        // Registro eventi (audit): transizioni di stato, invii, creazioni.
        $audit = self::tableAudit();
        $sqlAudit = "CREATE TABLE {$audit} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            richiesta_id BIGINT UNSIGNED NULL,
            token VARCHAR(64) NOT NULL DEFAULT '',
            evento VARCHAR(48) NOT NULL,
            dettaglio TEXT NULL,
            attore VARCHAR(100) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY richiesta_id (richiesta_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        dbDelta($sqlAllegati);
        dbDelta($sqlAudit);
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

    /**
     * Trova una richiesta dall'id.
     *
     * @return array<string,mixed>|null
     */
    public static function findById(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id),
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

    /**
     * Inserisce la riga di un allegato caricato.
     *
     * @param array{tipo:string,filename:string,original_name:string,mime:string,size:int} $a
     * @return int|false ID inserito o false in caso di errore.
     */
    public static function insertAllegato(int $richiestaId, array $a)
    {
        global $wpdb;
        $ok = $wpdb->insert(
            self::tableAllegati(),
            [
                'richiesta_id'  => $richiestaId,
                'tipo'          => $a['tipo'],
                'filename'      => $a['filename'],
                'original_name' => $a['original_name'],
                'mime'          => $a['mime'],
                'size'          => $a['size'],
                'created_at'    => gmdate('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        return $ok ? (int) $wpdb->insert_id : false;
    }

    /**
     * Allegati di una richiesta.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function listAllegati(int $richiestaId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::tableAllegati() . ' WHERE richiesta_id = %d ORDER BY id ASC',
                $richiestaId
            ),
            ARRAY_A
        );
        return is_array($rows) ? $rows : [];
    }

    /**
     * Trova un singolo allegato dall'id (per il download admin).
     *
     * @return array<string,mixed>|null
     */
    public static function findAllegato(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . self::tableAllegati() . ' WHERE id = %d', $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Costruisce WHERE + parametri condivisi da list() e count().
     *
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function buildWhere(string $stato, string $search): array
    {
        $where = [];
        $params = [];

        if ($stato !== '') {
            $where[] = 'stato = %s';
            $params[] = $stato;
        }
        if ($search !== '') {
            $where[] = 'token LIKE %s';
            $params[] = '%' . $GLOBALS['wpdb']->esc_like($search) . '%';
        }

        $sql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        return [$sql, $params];
    }

    /**
     * Lista paginata per il pannello admin (con filtri).
     *
     * @return array<int,array<string,mixed>> Righe con 'dati' decodificato.
     */
    public static function list(string $stato = '', string $search = '', int $limit = 20, int $offset = 0): array
    {
        global $wpdb;
        [$whereSql, $params] = self::buildWhere($stato, $search);

        $sql = 'SELECT * FROM ' . self::table() . $whereSql
            . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['dati'] = json_decode((string) $row['dati'], true) ?: [];
        }
        unset($row);

        return $rows;
    }

    /** Conteggio totale per la paginazione. */
    public static function count(string $stato = '', string $search = ''): int
    {
        global $wpdb;
        [$whereSql, $params] = self::buildWhere($stato, $search);

        $sql = 'SELECT COUNT(*) FROM ' . self::table() . $whereSql;
        $total = $params === []
            ? $wpdb->get_var($sql)
            : $wpdb->get_var($wpdb->prepare($sql, $params));

        return (int) $total;
    }

    // ------------------------------------------------------------------ AUDIT

    /**
     * Registra una riga di audit.
     *
     * @return int|false ID inserito o false in caso di errore.
     */
    public static function insertAudit(
        int $richiestaId,
        string $token,
        string $evento,
        string $dettaglio,
        string $attore,
        string $ip
    ) {
        global $wpdb;
        $ok = $wpdb->insert(
            self::tableAudit(),
            [
                'richiesta_id' => $richiestaId > 0 ? $richiestaId : null,
                'token'        => $token,
                'evento'       => $evento,
                'dettaglio'    => $dettaglio,
                'attore'       => $attore,
                'ip'           => $ip,
                'created_at'   => gmdate('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $ok ? (int) $wpdb->insert_id : false;
    }

    /**
     * Cronologia eventi di una richiesta (più recenti in alto).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function listAudit(int $richiestaId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . self::tableAudit() . ' WHERE richiesta_id = %d ORDER BY id DESC',
                $richiestaId
            ),
            ARRAY_A
        );
        return is_array($rows) ? $rows : [];
    }
}
