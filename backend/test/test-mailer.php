<?php

declare(strict_types=1);

/**
 * Test standalone del Mailer (S13b) senza WordPress.
 *
 * Stuba le funzioni WP usate da Mailer.php e verifica il calcolo dei
 * destinatari: Cc multipli, deduplica, scarto degli indirizzi non validi,
 * fallback quando il richiedente non ha email.
 *
 * Uso:  php test-mailer.php
 */

// ---------------------------------------------------------------------------
// Stub delle funzioni WordPress.
// ---------------------------------------------------------------------------

/** @var array<int,array<string,mixed>> Registro delle chiamate a wp_mail. */
$GLOBALS['sent'] = [];

/** @var array<string,mixed> Valori restituiti dai filtri, impostati nei test. */
$GLOBALS['filters'] = [];

function wp_mail($to, $subject, $message, $headers = [], $attachments = [])
{
    $GLOBALS['sent'][] = [
        'to'          => $to,
        'subject'     => $subject,
        'message'     => $message,
        'headers'     => $headers,
        'attachments' => $attachments,
    ];
    return true;
}

function is_email($email)
{
    return (bool) filter_var((string) $email, FILTER_VALIDATE_EMAIL);
}

function apply_filters($hook, $value)
{
    return $GLOBALS['filters'][$hook] ?? $value;
}

function get_option($name)
{
    return $name === 'admin_email' ? 'admin@example.test' : '';
}

function get_site_url()
{
    return 'https://gestionale.formedillecce.it/cms';
}

function wp_parse_url($url, $component = -1)
{
    return parse_url((string) $url, $component);
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

// ---------------------------------------------------------------------------
// Mini helper di asserzione.
// ---------------------------------------------------------------------------

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  PASS  $label\n";
        return;
    }
    $failed++;
    echo "  FAIL  $label\n";
}

/** Estrae il valore dell'header Cc dall'ultima email inviata. */
function ccOf(array $mail): string
{
    foreach ((array) ($mail['headers'] ?? []) as $h) {
        if (stripos($h, 'Cc:') === 0) {
            return trim(substr($h, 3));
        }
    }
    return '';
}

/**
 * Destinatari come array: wp_mail accetta sia stringa sia array, il Mailer
 * passa sempre un array ma il test resta tollerante.
 *
 * @return array<int,string>
 */
function toOf(array $mail): array
{
    $to = $mail['to'] ?? [];
    return is_array($to) ? $to : [$to];
}

/** Invia una "pratica inserita", che è l'email con FORMEDIL in Cc. */
function inviaConCc(array $dati, string $token = 'FME-TEST-CC00-0000'): void
{
    Mailer::praticaInserita($dati, $token, 'https://gestionale.formedillecce.it/invio/' . $token, '/pdf/inesistente.pdf');
}

function reset_state(array $filters): void
{
    $GLOBALS['sent'] = [];
    $GLOBALS['filters'] = $filters;
}

$CC_REALI = 'responsabileareasicurezza@formedillecce.it, direttore@formedillecce.it';

// ---------------------------------------------------------------------------
// 1. Configurazione reale: due caselle in Cc, mittente no-reply.
// ---------------------------------------------------------------------------

echo "\n1. Configurazione di produzione (2 caselle in Cc)\n";

reset_state([
    'formedil_admin_email' => $CC_REALI,
    'formedil_mail_from'   => 'no-reply@formedillecce.it',
]);

Mailer::praticaInserita(
    ['azienda_ragione_sociale' => 'Edilizia Rossi Srl', 'azienda_email' => 'cliente@example.test'],
    'FME-TEST-0001-AAAA',
    'https://gestionale.formedillecce.it/invio/FME-TEST-0001-AAAA',
    '/percorso/inesistente.pdf'
);

$mail = $GLOBALS['sent'][0] ?? [];
check('inviata una email', count($GLOBALS['sent']) === 1);
check('destinatario = richiedente', toOf($mail) === ['cliente@example.test']);
check('Cc contiene responsabileareasicurezza', str_contains(ccOf($mail), 'responsabileareasicurezza@formedillecce.it'));
check('Cc contiene direttore', str_contains(ccOf($mail), 'direttore@formedillecce.it'));
check('mittente = no-reply@formedillecce.it', (bool) preg_grep('/^From:.*no-reply@formedillecce\.it/', $mail['headers']));
check(
    'link di invio nel corpo punta a gestionale.formedillecce.it',
    str_contains((string) ($mail['message'] ?? ''), 'https://gestionale.formedillecce.it/invio/FME-TEST-0001-AAAA')
);
check(
    'il corpo non contiene il vecchio dominio moduli.*',
    !str_contains((string) ($mail['message'] ?? ''), 'moduli.formedillecce.it')
);

// ---------------------------------------------------------------------------
// 2. Richiedente senza email: le notifiche vanno alle caselle FORMEDIL.
// ---------------------------------------------------------------------------

echo "\n2. Richiedente senza email\n";

reset_state(['formedil_admin_email' => $CC_REALI]);

inviaConCc(['azienda_ragione_sociale' => 'Impresa Senza Mail'], 'FME-TEST-0002-BBBB');

$mail = $GLOBALS['sent'][0] ?? [];
check('inviata una email', count($GLOBALS['sent']) === 1);
check('destinatario = le 2 caselle FORMEDIL', count(toOf($mail)) === 2);
check('nessun Cc duplicato', ccOf($mail) === '');

// ---------------------------------------------------------------------------
// 3. Deduplica: il richiedente è anche una delle caselle FORMEDIL.
// ---------------------------------------------------------------------------

echo "\n3. Richiedente coincidente con una casella FORMEDIL\n";

reset_state(['formedil_admin_email' => $CC_REALI]);

inviaConCc(
    ['azienda_email' => 'DIRETTORE@formedillecce.it', 'azienda_ragione_sociale' => 'Test'],
    'FME-TEST-0003-CCCC'
);

$cc = ccOf($GLOBALS['sent'][0] ?? []);
check('direttore NON è anche in Cc', stripos($cc, 'direttore@') === false);
check('l\'altra casella resta in Cc', str_contains($cc, 'responsabileareasicurezza@formedillecce.it'));

// ---------------------------------------------------------------------------
// 4. Indirizzi sporchi: spazi, voci vuote, email non valida.
// ---------------------------------------------------------------------------

echo "\n4. Lista con indirizzi non validi\n";

reset_state(['formedil_admin_email' => '  valida@formedillecce.it ,, non-una-email, , altra@formedillecce.it ']);

inviaConCc(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0004-DDDD');

$cc = ccOf($GLOBALS['sent'][0] ?? []);
check('tiene le due valide', str_contains($cc, 'valida@formedillecce.it') && str_contains($cc, 'altra@formedillecce.it'));
check('scarta la stringa non valida', !str_contains($cc, 'non-una-email'));

// ---------------------------------------------------------------------------
// 5. Retrocompatibilità: filtro che restituisce una stringa singola o un array.
// ---------------------------------------------------------------------------

echo "\n5. Forme accettate dal filtro\n";

reset_state(['formedil_admin_email' => 'singola@formedillecce.it']);
inviaConCc(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0005-EEEE');
check('stringa singola (vecchio formato)', ccOf($GLOBALS['sent'][0] ?? []) === 'singola@formedillecce.it');

reset_state(['formedil_admin_email' => ['uno@formedillecce.it', 'due@formedillecce.it']]);
inviaConCc(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0006-FFFF');
check('array di indirizzi', ccOf($GLOBALS['sent'][0] ?? []) === 'uno@formedillecce.it, due@formedillecce.it');

// ---------------------------------------------------------------------------
// 6. Fallback ENTE: nessuna azienda_email, si usa org_email.
// ---------------------------------------------------------------------------

echo "\n6. Fallback destinatario variante ENTE\n";

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiRicevuti(
    ['org_ragione_sociale' => 'Ente Formatore', 'org_email' => 'ente@example.test'],
    'FME-TEST-0007-GGGG'
);
check('destinatario = org_email', toOf($GLOBALS['sent'][0] ?? []) === ['ente@example.test']);

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiRicevuti(
    ['imprese' => [['azienda_email' => 'prima-impresa@example.test']]],
    'FME-TEST-0008-HHHH'
);
check('destinatario = email prima impresa', toOf($GLOBALS['sent'][0] ?? []) === ['prima-impresa@example.test']);

// ---------------------------------------------------------------------------
// 7. S14 — la conferma al richiedente non ha più FORMEDIL in Cc.
// ---------------------------------------------------------------------------

echo "\n7. Conferma al richiedente senza Cc (S14)\n";

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiRicevuti(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0009-IIII');

$mail = $GLOBALS['sent'][0] ?? [];
check('inviata al solo richiedente', ($mail['to'] ?? []) === ['cliente@example.test']);
check('nessun Cc a FORMEDIL', ccOf($mail) === '');

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiRicevuti(['azienda_ragione_sociale' => 'Senza Email'], 'FME-TEST-0010-JJJJ');
check('senza email del richiedente non parte nulla', $GLOBALS['sent'] === []);

// ---------------------------------------------------------------------------
// 8. S14 — notifica interna con i documenti allegati.
// ---------------------------------------------------------------------------

echo "\n8. Notifica interna con allegati\n";

// File finti su disco per misurare i pesi.
$tmp = sys_get_temp_dir() . '/formedil-test-allegati';
if (!is_dir($tmp)) {
    mkdir($tmp, 0777, true);
}
$firmatoPath = $tmp . '/firmato.pdf';
$piccoloPath = $tmp . '/piccolo.pdf';
$enormePath  = $tmp . '/enorme.pdf';
file_put_contents($firmatoPath, str_repeat('F', 1024));
file_put_contents($piccoloPath, str_repeat('P', 2048));
file_put_contents($enormePath, str_repeat('E', 5 * 1024 * 1024));

$allegatiOk = [
    ['tipo' => 'ALLEGATO', 'original_name' => 'visura.pdf', 'path' => $piccoloPath],
    ['tipo' => 'FIRMATO', 'original_name' => 'modulo-firmato.pdf', 'path' => $firmatoPath],
];

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiPerVerifica(
    ['azienda_ragione_sociale' => 'Edil Prova Srl', 'azienda_email' => 'cliente@example.test'],
    'FME-TEST-0011-KKKK',
    $allegatiOk,
    'https://gestionale.formedillecce.it/cms/wp-admin/admin.php?page=formedil-richieste&token=FME-TEST-0011-KKKK'
);

$mail = $GLOBALS['sent'][0] ?? [];
check('destinatari = sole caselle FORMEDIL', is_array($mail['to'] ?? null) && count($mail['to']) === 2);
check('il richiedente NON è tra i destinatari', !in_array('cliente@example.test', (array) ($mail['to'] ?? []), true));
check('due file allegati', count((array) ($mail['attachments'] ?? [])) === 2);
check('il firmato è il primo allegato', (($mail['attachments'][0] ?? '') === $firmatoPath));
check('oggetto distinto dalla conferma', str_contains((string) ($mail['subject'] ?? ''), 'da verificare'));
check('link al pannello nel corpo', str_contains((string) ($mail['message'] ?? ''), 'page=formedil-richieste'));

// ---------------------------------------------------------------------------
// 9. S14 — guardia sul peso: gli allegati oltre soglia restano fuori.
// ---------------------------------------------------------------------------

echo "\n9. Allegati oltre il tetto SMTP\n";

$allegatiPesanti = [
    ['tipo' => 'FIRMATO', 'original_name' => 'modulo-firmato.pdf', 'path' => $firmatoPath],
    ['tipo' => 'ALLEGATO', 'original_name' => 'foto-cantiere.pdf', 'path' => $enormePath],
];

reset_state([
    'formedil_admin_email'     => $CC_REALI,
    'formedil_mail_max_attach' => 1024 * 1024, // 1MB: il file da 5MB non entra
]);
Mailer::documentiPerVerifica(['azienda_ragione_sociale' => 'Test'], 'FME-TEST-0012-LLLL', $allegatiPesanti);

$mail = $GLOBALS['sent'][0] ?? [];
check('allegato solo il firmato', count((array) ($mail['attachments'] ?? [])) === 1);
check('il file escluso è citato nel corpo', str_contains((string) ($mail['message'] ?? ''), 'foto-cantiere.pdf'));
check('avviso di dimensione presente', str_contains((string) ($mail['message'] ?? ''), 'Non allegati per dimensione'));

// Interruttore "allega documenti" spento: tetto a zero, resta il solo firmato.
reset_state([
    'formedil_admin_email'     => $CC_REALI,
    'formedil_mail_max_attach' => 0,
]);
Mailer::documentiPerVerifica(['azienda_ragione_sociale' => 'Test'], 'FME-TEST-0013-MMMM', $allegatiOk);
check('con allegati disattivati resta solo il firmato', count((array) ($GLOBALS['sent'][0]['attachments'] ?? [])) === 1);

// File mancante su disco: non deve far fallire l'invio.
reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiPerVerifica(
    [],
    'FME-TEST-0014-NNNN',
    [['tipo' => 'FIRMATO', 'original_name' => 'sparito.pdf', 'path' => $tmp . '/non-esiste.pdf']]
);
check('file mancante: email inviata comunque', count($GLOBALS['sent']) === 1);
check('file mancante: nessun allegato', ($GLOBALS['sent'][0]['attachments'] ?? []) === []);

// Nessun destinatario interno configurato: non si invia nulla.
reset_state(['formedil_admin_email' => '']);
Mailer::documentiPerVerifica(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0015-OOOO', $allegatiOk);
check('senza caselle FORMEDIL non parte nulla', $GLOBALS['sent'] === []);

// ---------------------------------------------------------------------------
// 10. S14 — logo nell'intestazione.
// ---------------------------------------------------------------------------

echo "\n10. Logo nell'intestazione\n";

reset_state([
    'formedil_admin_email'  => $CC_REALI,
    'formedil_mail_logo_url' => 'https://gestionale.formedillecce.it/cms/wp-content/plugins/formedil-moduli/templates/assets/logo-email.png',
]);
Mailer::documentiRicevuti(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0016-PPPP');

$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('tag img col logo', str_contains($html, 'logo-email.png'));
check('testo alternativo presente', str_contains($html, 'alt="FORMEDIL LECCE"'));

// Senza URL del logo si torna al wordmark testuale.
reset_state(['formedil_admin_email' => $CC_REALI, 'formedil_mail_logo_url' => '']);
Mailer::documentiRicevuti(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0017-QQQQ');
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('senza logo resta il wordmark', str_contains($html, 'FORMEDIL LECCE') && !str_contains($html, '<img'));

// ---------------------------------------------------------------------------
// 11. S15 — registro formale (dare del Lei) nelle email al richiedente.
// ---------------------------------------------------------------------------

echo "\n11. Registro formale (S15)\n";

reset_state(['formedil_admin_email' => $CC_REALI]);
inviaConCc(['azienda_email' => 'cliente@example.test', 'azienda_ragione_sociale' => 'Edil Prova'], 'FME-TEST-0018-RRRR');
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('pratica inserita: usa "Sua richiesta"', str_contains($html, 'Sua richiesta'));
check('pratica inserita: niente "la tua richiesta"', !str_contains($html, 'la tua richiesta'));

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::documentiRicevuti(['azienda_email' => 'cliente@example.test'], 'FME-TEST-0019-SSSS');
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('conferma: testo sui 15 giorni', str_contains($html, '15 giorni') && str_contains($html, 'aggiornamenti sull\'esito'));
check('conferma: riferimento ASR 17/04/2025', str_contains($html, 'ASR del 17/04/2025'));
check('conferma: procedere autonomamente', str_contains($html, 'procedere autonomamente'));
check('conferma: niente "Riceverai"', !str_contains($html, 'Riceverai'));

// ---------------------------------------------------------------------------
// 12. S15 — email di riscontro, tre esiti.
// ---------------------------------------------------------------------------

echo "\n12. Riscontro: collaborazione accettata\n";

$datiRic = ['azienda_email' => 'cliente@example.test', 'azienda_ragione_sociale' => 'Edil Prova Srl'];

reset_state(['formedil_admin_email' => $CC_REALI]);
$ok = Mailer::riscontro($datiRic, 'FME-TEST-0020-TTTT', Mailer::ESITO_ACCETTATA);
$mail = $GLOBALS['sent'][0] ?? [];
$html = (string) ($mail['message'] ?? '');
check('invio riuscito', $ok === true);
check('destinatario = solo richiedente', toOf($mail) === ['cliente@example.test']);
check('nessun Cc a FORMEDIL', ccOf($mail) === '');
check('oggetto breve col codice', ($mail['subject'] ?? '') === 'FORMEDIL Lecce · Riscontro alla richiesta (Cod. FME-TEST-0020-TTTT)');
check('oggetto per esteso nel corpo', str_contains($html, 'art. 37, comma 12, D.Lgs. 81/08'));
check('richiedente e codice in evidenza', str_contains($html, 'Edil Prova Srl') && str_contains($html, 'FME-TEST-0020-TTTT'));
check('testo di accettazione', str_contains($html, 'espressamente accettata'));
check('firma FORMEDIL', str_contains($html, 'Distinti saluti'));

echo "\n13. Riscontro: accolta con indicazioni\n";

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::riscontro(
    $datiRic,
    'FME-TEST-0021-UUUU',
    Mailer::ESITO_CON_INDICAZIONI,
    "Ridurre i partecipanti a 20 per aula.\nIntegrare il modulo sui DPI."
);
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('testo di accoglimento condizionato', str_contains($html, 'accolta'));
check('prima indicazione presente', str_contains($html, 'Ridurre i partecipanti a 20 per aula.'));
check('seconda indicazione presente', str_contains($html, 'Integrare il modulo sui DPI.'));
check('gli a capo diventano <br>', str_contains($html, '<br />') || str_contains($html, '<br>'));

echo "\n14. Riscontro: non accolta\n";

reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::riscontro($datiRic, 'FME-TEST-0022-VVVV', Mailer::ESITO_NON_ACCOLTA, 'Documentazione incompleta.');
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('testo di rifiuto', str_contains($html, 'non pu&ograve; essere accolta') || str_contains($html, 'non può essere accolta'));
check('motivazione presente', str_contains($html, 'Documentazione incompleta.'));

echo "\n15. Riscontro: casi limite\n";

// Nessuna email del richiedente: non si invia e si segnala il fallimento.
reset_state(['formedil_admin_email' => $CC_REALI]);
$ok = Mailer::riscontro(['azienda_ragione_sociale' => 'Senza Email'], 'FME-TEST-0023-WWWW', Mailer::ESITO_ACCETTATA);
check('senza email restituisce false', $ok === false);
check('senza email non invia nulla', $GLOBALS['sent'] === []);

// Il testo libero non deve poter iniettare HTML.
reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::riscontro($datiRic, 'FME-TEST-0024-XXXX', Mailer::ESITO_NON_ACCOLTA, '<script>alert(1)</script> & "virgolette"');
$html = (string) ($GLOBALS['sent'][0]['message'] ?? '');
check('script neutralizzato', !str_contains($html, '<script>'));
check('caratteri speciali convertiti', str_contains($html, '&lt;script&gt;') && str_contains($html, '&amp;'));

// Esito non riconosciuto: l'email parte ma senza blocco esito (nessun crash).
reset_state(['formedil_admin_email' => $CC_REALI]);
$ok = Mailer::riscontro($datiRic, 'FME-TEST-0025-YYYY', 'ESITO_INESISTENTE');
check('esito ignoto non manda in errore', $ok === true);

// Le etichette servono al pannello: devono coprire i tre esiti.
$et = Mailer::esiti();
check('tre esiti disponibili', count($et) === 3);
check('chiavi coerenti con le costanti', isset($et[Mailer::ESITO_ACCETTATA], $et[Mailer::ESITO_CON_INDICAZIONI], $et[Mailer::ESITO_NON_ACCOLTA]));

// L'anteprima deve essere identica al messaggio inviato.
reset_state(['formedil_admin_email' => $CC_REALI]);
Mailer::riscontro($datiRic, 'FME-TEST-0026-ZZZZ', Mailer::ESITO_CON_INDICAZIONI, 'Nota di prova.');
$inviato = (string) ($GLOBALS['sent'][0]['message'] ?? '');
$anteprima = Mailer::anteprimaRiscontro($datiRic, 'FME-TEST-0026-ZZZZ', Mailer::ESITO_CON_INDICAZIONI, 'Nota di prova.');
check('anteprima identica al messaggio inviato', $inviato === $anteprima);

// ---------------------------------------------------------------------------
// Pulizia dei file temporanei.
// ---------------------------------------------------------------------------

foreach ([$firmatoPath, $piccoloPath, $enormePath] as $f) {
    if (is_file($f)) {
        unlink($f);
    }
}

echo "\n----------------------------------------\n";
echo "PASS: $passed   FAIL: $failed\n";
exit($failed === 0 ? 0 : 1);
