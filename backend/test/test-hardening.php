<?php
/**
 * Test standalone dell'hardening S8 — niente WordPress, niente DB.
 *
 * Verifica due cose isolabili:
 *   1) RateLimiter: dopo N tentativi nella finestra, blocca con Retry-After.
 *   2) AllegatoStorage::validateCollection: cap su numero file e dimensione totale.
 *
 * USO (con qualsiasi PHP 8):
 *   php test-hardening.php
 *
 * Le funzioni WordPress usate dal RateLimiter (get_transient, set_transient,
 * apply_filters) sono stubate con un transient store in memoria.
 */

declare(strict_types=1);

// ----------------------------------------------------------- Stub WordPress

$GLOBALS['__transients'] = [];

if (!function_exists('get_transient')) {
    function get_transient(string $key)
    {
        $store = $GLOBALS['__transients'];
        if (!isset($store[$key])) {
            return false;
        }
        [$value, $expires] = $store[$key];
        if ($expires !== 0 && $expires < time()) {
            unset($GLOBALS['__transients'][$key]);
            return false;
        }
        return $value;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, $value, int $ttl = 0): bool
    {
        $GLOBALS['__transients'][$key] = [$value, $ttl > 0 ? time() + $ttl : 0];
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value, ...$args)
    {
        return $value; // Nessun filtro registrato nei test: si usano i default.
    }
}

$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

require realpath(__DIR__ . '/../formedil-moduli') . '/src/autoload.php';

use Formedil\Moduli\Storage\AllegatoStorage;
use Formedil\Moduli\Support\RateLimiter;

// ----------------------------------------------------------- Mini framework

$passed = 0;
$failed = 0;

function check(string $label, bool $cond): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  \033[32mPASS\033[0m  {$label}\n";
    } else {
        $failed++;
        echo "  \033[31mFAIL\033[0m  {$label}\n";
    }
}

// ------------------------------------------------------------- RateLimiter

echo "RateLimiter\n";

// Limite: 3 tentativi in 60s. I primi 3 passano, il 4° è bloccato.
$ok = true;
for ($i = 1; $i <= 3; $i++) {
    $r = RateLimiter::check('test', 3, 60);
    $ok = $ok && $r['ok'] === true;
}
check('i primi 3 tentativi sono ammessi', $ok);

$r4 = RateLimiter::check('test', 3, 60);
check('il 4° tentativo è bloccato (ok=false)', $r4['ok'] === false);
check('la risposta bloccata espone Retry-After > 0', $r4['retry_after'] > 0);

// Bucket diverso => contatore indipendente.
$rAltro = RateLimiter::check('altro', 3, 60);
check('un bucket diverso non è influenzato', $rAltro['ok'] === true);

// max = 0 => limite disattivato.
$rOff = RateLimiter::check('disattivo', 0, 60);
check('max=0 disattiva il rate limit', $rOff['ok'] === true);

// Reset finestra: simulo lo scadere azzerando il transient store.
$GLOBALS['__transients'] = [];
$rReset = RateLimiter::check('test', 3, 60);
check('dopo il reset della finestra si riparte ammessi', $rReset['ok'] === true);

// ----------------------------------------------- AllegatoStorage cap d'insieme

echo "\nAllegatoStorage::validateCollection\n";

$firmato1mb = ['size' => 1 * 1024 * 1024];

// Entro i limiti.
$allegatiOk = array_fill(0, 3, ['size' => 1 * 1024 * 1024]);
check('3 allegati piccoli sono ammessi', AllegatoStorage::validateCollection($firmato1mb, $allegatiOk) === '');

// Troppi file.
$troppi = array_fill(0, AllegatoStorage::MAX_ALLEGATI + 1, ['size' => 1024]);
check('oltre MAX_ALLEGATI viene rifiutato', AllegatoStorage::validateCollection($firmato1mb, $troppi) !== '');

// Esattamente MAX_ALLEGATI è ammesso (per dimensione contenuta).
$alLimite = array_fill(0, AllegatoStorage::MAX_ALLEGATI, ['size' => 1024]);
check('esattamente MAX_ALLEGATI è ammesso', AllegatoStorage::validateCollection($firmato1mb, $alLimite) === '');

// Dimensione totale oltre il tetto.
$grosso = ['size' => AllegatoStorage::MAX_TOTAL_SIZE];
$extra = [['size' => 1 * 1024 * 1024]];
check('oltre MAX_TOTAL_SIZE viene rifiutato', AllegatoStorage::validateCollection($grosso, $extra) !== '');

// Nessun allegato (solo firmato) è ammesso.
check('solo PDF firmato, nessun allegato, è ammesso', AllegatoStorage::validateCollection($firmato1mb, []) === '');

// ------------------------------------------------------------------- Esito

echo "\n----------------------------------------\n";
echo "Totale: {$passed} passati, {$failed} falliti\n";
exit($failed === 0 ? 0 : 1);
