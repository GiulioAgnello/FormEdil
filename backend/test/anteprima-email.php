<?php

declare(strict_types=1);

/**
 * Genera un'anteprima navigabile delle email transazionali, senza WordPress.
 *
 * Serve per rifinire la grafica senza dover creare pratiche vere: intercetta
 * le chiamate a wp_mail e salva i corpi HTML in un unico file.
 *
 * Uso:  php anteprima-email.php   →   anteprima-email.html
 */

$GLOBALS['sent'] = [];
$GLOBALS['filters'] = [
    'formedil_admin_email' => 'responsabileareasicurezza@formedillecce.it, direttore@formedillecce.it',
    'formedil_mail_from'   => 'no-reply@formedillecce.it',
    // Path relativo: così il logo si vede aprendo il file nel browser.
    'formedil_mail_logo_url' => '../formedil-moduli/templates/assets/logo-email.png',
];

function wp_mail($to, $subject, $message, $headers = [], $attachments = [])
{
    $GLOBALS['sent'][] = compact('to', 'subject', 'message', 'headers', 'attachments');
    return true;
}

function is_email($e)
{
    return (bool) filter_var((string) $e, FILTER_VALIDATE_EMAIL);
}

function apply_filters($hook, $value)
{
    return $GLOBALS['filters'][$hook] ?? $value;
}

function get_option($n)
{
    return $n === 'admin_email' ? 'admin@formedillecce.it' : '';
}

function get_site_url()
{
    return 'https://gestionale.formedillecce.it/cms';
}

function wp_parse_url($u, $c = -1)
{
    return parse_url((string) $u, $c);
}

function esc_html($t)
{
    return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
}

function esc_url($u)
{
    return htmlspecialchars((string) $u, ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/../formedil-moduli/src/Service/Mailer.php';

use Formedil\Moduli\Service\Mailer;

$token = 'FME-ANTE-PRIM-A001';
$dati = [
    'azienda_ragione_sociale' => 'Edilizia Rossi Srl',
    'azienda_email'           => 'ufficio@ediliziarossi.it',
];

// File finti per mostrare l'elenco degli allegati.
$tmp = sys_get_temp_dir() . '/formedil-anteprima';
@mkdir($tmp, 0777, true);
file_put_contents($tmp . '/firmato.pdf', 'x');
file_put_contents($tmp . '/visura.pdf', 'x');

// 1. Pratica inserita (al richiedente, Cc a FORMEDIL).
Mailer::praticaInserita($dati, $token, 'https://gestionale.formedillecce.it/invio/' . $token, __FILE__);

// 2. Documenti ricevuti (solo richiedente).
Mailer::documentiRicevuti($dati, $token);

// 3. Notifica interna con allegati (solo FORMEDIL).
Mailer::documentiPerVerifica($dati, $token, [
    ['tipo' => 'FIRMATO', 'original_name' => 'modulo-firmato.pdf', 'path' => $tmp . '/firmato.pdf'],
    ['tipo' => 'ALLEGATO', 'original_name' => 'visura-camerale.pdf', 'path' => $tmp . '/visura.pdf'],
    ['tipo' => 'ALLEGATO', 'original_name' => 'planimetria-cantiere.pdf', 'path' => $tmp . '/mancante.pdf'],
], 'https://gestionale.formedillecce.it/cms/wp-admin/admin.php?page=formedil-richieste&token=' . $token);

// 4-6. Riscontro nei tre esiti previsti dall'organismo paritetico.
Mailer::riscontro($dati, $token, Mailer::ESITO_ACCETTATA);

Mailer::riscontro(
    $dati,
    $token,
    Mailer::ESITO_CON_INDICAZIONI,
    "Ridurre a 20 il numero di partecipanti per ciascuna aula.\n"
    . "Integrare il programma con il modulo sui dispositivi di protezione individuale.\n"
    . "Trasmettere l'attestato del docente prima dell'avvio del corso."
);

Mailer::riscontro(
    $dati,
    $token,
    Mailer::ESITO_NON_ACCOLTA,
    "La documentazione allegata risulta incompleta: manca il documento di identita' del datore di lavoro.\n"
    . "La durata indicata per il corso e' inferiore al minimo previsto dall'Accordo Stato Regioni."
);

// 7. Email di prova dal pannello.
Mailer::inviaProva('giulio@example.test');

// ---------------------------------------------------------------------------
// Composizione della pagina di anteprima.
// ---------------------------------------------------------------------------

$out = '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">'
    . '<title>Anteprima email FORMEDIL</title>'
    . '<style>body{margin:0;background:#E2E8F0;font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;}'
    . '.wrap{max-width:760px;margin:0 auto;padding:32px 16px;}'
    . 'h1{font-size:20px;color:#0F172A;}'
    . '.meta{background:#0F172A;color:#fff;border-radius:10px 10px 0 0;padding:14px 18px;font-size:13px;line-height:1.7;}'
    . '.meta b{color:#FBBF24;}'
    . '.frame{background:#F1F5F9;border-radius:0 0 10px 10px;margin-bottom:34px;overflow:hidden;}'
    . '</style></head><body><div class="wrap">'
    . '<h1>Anteprima delle email — FORMEDIL Lecce</h1>';

foreach ($GLOBALS['sent'] as $i => $m) {
    $to = is_array($m['to']) ? implode(', ', $m['to']) : (string) $m['to'];
    $cc = '';
    foreach ((array) $m['headers'] as $h) {
        if (stripos($h, 'Cc:') === 0) {
            $cc = trim(substr($h, 3));
        }
    }
    $allegati = [];
    foreach ((array) $m['attachments'] as $a) {
        $allegati[] = basename((string) $a);
    }

    $out .= '<div class="meta">'
        . '<b>' . ($i + 1) . '. ' . htmlspecialchars($m['subject']) . '</b><br>'
        . 'A: ' . htmlspecialchars($to) . '<br>'
        . ($cc !== '' ? 'Cc: ' . htmlspecialchars($cc) . '<br>' : '')
        . 'Allegati: ' . ($allegati === [] ? 'nessuno' : htmlspecialchars(implode(', ', $allegati)))
        . '</div><div class="frame">' . $m['message'] . '</div>';
}

$out .= '</div></body></html>';

file_put_contents(__DIR__ . '/anteprima-email.html', $out);

echo "Anteprima generata: " . __DIR__ . "/anteprima-email.html\n";
echo count($GLOBALS['sent']) . " email renderizzate.\n";
