<?php
/**
 * Autoloader PSR-4 minimale.
 *
 * In produzione si usa `composer install` (vedi composer.json). Questo loader
 * permette di far girare il plugin anche senza Composer, mappando il namespace
 * Formedil\Moduli\ sulla cartella src/.
 *
 * @package Formedil\Moduli
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Formedil\\Moduli\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Non è una nostra classe.
    }

    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
