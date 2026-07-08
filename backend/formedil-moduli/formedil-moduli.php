<?php
/**
 * Plugin Name: FORMEDIL Moduli
 * Description: Backend headless per la digitalizzazione dei moduli di richiesta collaborazione Art. 37 (IMPRESA/ENTE). Espone REST API custom in /wp-json/formedil/v1/.
 * Version: 0.1.0
 * Author: FORMEDIL Lecce
 * Requires PHP: 8.0
 *
 * @package Formedil\Moduli
 */

declare(strict_types=1);

namespace Formedil\Moduli;

if (!defined('ABSPATH')) {
    exit; // Accesso diretto non consentito.
}

// Costanti di base del plugin.
define('FORMEDIL_VERSION', '0.1.0');
define('FORMEDIL_REST_NAMESPACE', 'formedil/v1');
define('FORMEDIL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FORMEDIL_PLUGIN_URL', plugin_dir_url(__FILE__));
// Lo schema canonico è condiviso con il frontend (cartella /shared a livello di repo).
define('FORMEDIL_SCHEMA_PATH', FORMEDIL_PLUGIN_DIR . 'schema/form-schema.json');

// Autoload Composer (mPDF e dipendenze). Necessario in produzione.
$composerAutoload = FORMEDIL_PLUGIN_DIR . 'vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// Autoload PSR-4 delle classi del plugin (funziona anche senza Composer).
require_once FORMEDIL_PLUGIN_DIR . 'src/autoload.php';

// Creazione tabella e cartelle all'attivazione.
register_activation_hook(__FILE__, [Core\Activator::class, 'activate']);

/**
 * Bootstrap del plugin.
 */
function bootstrap(): void
{
    $plugin = new Core\Plugin();
    $plugin->register();
}

bootstrap();
