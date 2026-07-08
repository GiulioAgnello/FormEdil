<?php
/**
 * Anteprima HTML del PDF di richiesta — per iterare velocemente sulla grafica.
 *
 * Genera un file preview.html (in questa cartella) usando lo STESSO template
 * e lo STESSO CSS del PDF reale, ma renderizzato come pagina web. Così puoi
 * ritoccare templates/pdf-richiesta.css e ricaricare il browser, senza dover
 * rigenerare il PDF da WordPress ogni volta.
 *
 * USO (con il PHP di Local, o qualsiasi PHP 8):
 *   php render-preview.php            # variante IMPRESA (default)
 *   php render-preview.php ENTE       # variante ENTE
 *
 * NB: il QR è una funzione di mPDF (tag <barcode>): nell'anteprima web viene
 * sostituito da un riquadro segnaposto. Il layout/colori/tipografia sono fedeli.
 */

declare(strict_types=1);

$variante = strtoupper($argv[1] ?? 'IMPRESA');
if (!in_array($variante, ['IMPRESA', 'ENTE'], true)) {
    fwrite(STDERR, "Variante non valida: usa IMPRESA o ENTE\n");
    exit(1);
}

$pluginDir = realpath(__DIR__ . '/../formedil-moduli') . '/';

define('FORMEDIL_PLUGIN_DIR', $pluginDir);
define('FORMEDIL_SCHEMA_PATH', $pluginDir . 'schema/form-schema.json');

require $pluginDir . 'src/autoload.php';

use Formedil\Moduli\Schema\SchemaProvider;

// Dati di esempio (riusa il payload di test della variante richiesta).
$exampleFile = __DIR__ . '/richiesta-' . strtolower($variante) . '.example.json';
if (!is_file($exampleFile)) {
    $exampleFile = __DIR__ . '/richiesta-impresa.example.json';
}
$payload = json_decode((string) file_get_contents($exampleFile), true) ?: [];
$dati = $payload['dati'] ?? [];

$schema   = SchemaProvider::get();
$token    = 'FME-ANTE-PRIM-A001';
$invioUrl = 'https://moduli.formedillecce.it/invio/' . $token;
// Path relativo al logo, così il browser lo mostra aprendo preview.html.
$logoSrc  = '../formedil-moduli/templates/assets/logo.jpg';

// Render del template (stessa logica del PDF reale).
ob_start();
(static function () use ($pluginDir, $variante, $dati, $token, $invioUrl, $schema, $logoSrc): void {
    include $pluginDir . 'templates/pdf-richiesta.php';
})();
$body = (string) ob_get_clean();

// Il tag <barcode> è solo per mPDF: nell'anteprima mettiamo un segnaposto.
$body = preg_replace(
    '/<barcode[^>]*\/>/i',
    '<div style="width:80pt;height:80pt;border:1px dashed #999;text-align:center;'
        . 'font-size:8pt;color:#999;line-height:80pt;">QR</div>',
    $body
);

$css = (string) file_get_contents($pluginDir . 'templates/pdf-richiesta.css');

// Pagina A4 simulata per dare un'idea reale dei margini.
$html = '<!DOCTYPE html><html lang="it"><head><meta charset="utf-8">'
    . '<title>Anteprima PDF — ' . htmlspecialchars($variante) . '</title><style>'
    . 'html,body{background:#e9e9e9;margin:0;}'
    . '.sheet{background:#fff;width:210mm;min-height:297mm;margin:16px auto;'
    . 'padding:16mm 14mm;box-shadow:0 2px 12px rgba(0,0,0,.2);box-sizing:border-box;}'
    . $css
    . '</style></head><body><div class="sheet">'
    . $body
    . '</div></body></html>';

$out = __DIR__ . '/preview.html';
file_put_contents($out, $html);
echo "Anteprima generata: {$out}\n";
echo "Apri il file nel browser. Variante: {$variante}\n";
