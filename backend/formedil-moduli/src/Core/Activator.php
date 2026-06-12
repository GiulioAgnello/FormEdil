<?php

declare(strict_types=1);

namespace Formedil\Moduli\Core;

use Formedil\Moduli\Data\Repository;

/**
 * Operazioni eseguite all'attivazione del plugin.
 * Crea/aggiorna lo schema della tabella e prepara la cartella PDF protetta.
 */
final class Activator
{
    public static function activate(): void
    {
        Repository::createTable();
        self::ensureStorageDir();
    }

    /**
     * Crea la cartella per i PDF generati, fuori dalla portata diretta del web.
     * Sta sotto wp-content/uploads/formedil/ con protezione .htaccess.
     */
    private static function ensureStorageDir(): void
    {
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'formedil';

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        // Nega l'accesso diretto: i PDF si scaricano solo via endpoint REST.
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
        $index = $dir . '/index.php';
        if (!is_file($index)) {
            file_put_contents($index, "<?php // Silence is golden.\n");
        }
    }
}
