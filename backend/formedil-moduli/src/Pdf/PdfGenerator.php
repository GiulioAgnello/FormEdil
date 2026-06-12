<?php

declare(strict_types=1);

namespace Formedil\Moduli\Pdf;

use Formedil\Moduli\Schema\SchemaProvider;
use Mpdf\Mpdf;

/**
 * Genera il PDF della richiesta a partire dai dati e dal token.
 * Usa mPDF e il template fedele al modulo ufficiale.
 */
final class PdfGenerator
{
    /**
     * Crea il PDF e lo salva nella cartella protetta.
     *
     * @param array<string,mixed> $dati
     * @return string Nome del file PDF generato.
     * @throws \RuntimeException se la generazione fallisce.
     */
    public static function generate(string $variante, array $dati, string $token, string $invioUrl): string
    {
        $html = self::renderTemplate($variante, $dati, $token, $invioUrl);

        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'formedil';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $filename = 'richiesta-' . $token . '.pdf';

        try {
            $mpdf = new Mpdf([
                'tempDir'       => trailingslashit($dir) . '.tmp',
                'margin_top'    => 14,
                'margin_bottom' => 14,
                'margin_left'   => 14,
                'margin_right'  => 14,
            ]);
            $mpdf->SetTitle('Richiesta collaborazione FORMEDIL Lecce');
            $mpdf->WriteHTML($html);
            $mpdf->Output(trailingslashit($dir) . $filename, \Mpdf\Output\Destination::FILE);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Generazione PDF fallita: ' . $e->getMessage(), 0, $e);
        }

        return $filename;
    }

    /**
     * @param array<string,mixed> $dati
     */
    private static function renderTemplate(string $variante, array $dati, string $token, string $invioUrl): string
    {
        $schema = SchemaProvider::get();
        $templatePath = FORMEDIL_PLUGIN_DIR . 'templates/pdf-richiesta.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template PDF non trovato.');
        }

        // Variabili rese disponibili al template.
        // phpcs:ignore
        ob_start();
        (static function () use ($templatePath, $variante, $dati, $token, $invioUrl, $schema): void {
            include $templatePath;
        })();

        return (string) ob_get_clean();
    }

    public static function path(string $filename): string
    {
        $upload = wp_upload_dir();
        return trailingslashit($upload['basedir']) . 'formedil/' . $filename;
    }
}
