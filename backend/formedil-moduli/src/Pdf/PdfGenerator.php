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
        $css = self::loadCss();

        try {
            $mpdf = new Mpdf([
                'tempDir'        => trailingslashit($dir) . '.tmp',
                'default_font'   => 'dejavusans',
                'margin_top'     => 16,
                'margin_bottom'  => 16,
                'margin_left'    => 14,
                'margin_right'   => 14,
                'margin_footer'  => 7,
            ]);
            $mpdf->SetTitle('Richiesta collaborazione FORMEDIL Lecce');
            $mpdf->SetHTMLFooter(self::footerHtml($token));

            if ($css !== '') {
                $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
            }
            $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->Output(trailingslashit($dir) . $filename, \Mpdf\Output\Destination::FILE);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Generazione PDF fallita: ' . $e->getMessage(), 0, $e);
        }

        return $filename;
    }

    /** Carica il foglio di stile del PDF (file separato, modificabile a mano). */
    private static function loadCss(): string
    {
        $path = FORMEDIL_PLUGIN_DIR . 'templates/pdf-richiesta.css';
        $css = is_file($path) ? file_get_contents($path) : '';
        return $css !== false ? (string) $css : '';
    }

    /**
     * Path assoluto del logo per il letterhead.
     * Cerca templates/assets/logo.(png|jpg|jpeg); filtrabile con 'formedil_pdf_logo'.
     */
    private static function logoSrc(): string
    {
        $base = FORMEDIL_PLUGIN_DIR . 'templates/assets/';
        $found = '';
        foreach (['logo.png', 'logo.jpg', 'logo.jpeg'] as $name) {
            if (is_file($base . $name)) {
                $found = $base . $name;
                break;
            }
        }
        if (function_exists('apply_filters')) {
            $found = (string) apply_filters('formedil_pdf_logo', $found);
        }
        return $found;
    }

    /** Footer HTML con paginazione e token (ripetuto su ogni pagina). */
    private static function footerHtml(string $token): string
    {
        $tok = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        return '<table class="pdf-footer" style="width:100%;">'
            . '<tr>'
            . '<td style="text-align:left;">Richiesta collaborazione FORMEDIL Lecce · Art. 37 c.12</td>'
            . '<td style="text-align:center;">Cod. ' . $tok . '</td>'
            . '<td style="text-align:right;">Pag. {PAGENO} di {nbpg}</td>'
            . '</tr></table>';
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

        $logoSrc = self::logoSrc();

        // Variabili rese disponibili al template.
        // phpcs:ignore
        ob_start();
        (static function () use ($templatePath, $variante, $dati, $token, $invioUrl, $schema, $logoSrc): void {
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
