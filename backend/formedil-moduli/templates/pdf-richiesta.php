<?php
/**
 * Template HTML del PDF di richiesta collaborazione (DTL / ENTE).
 * Reso fedele al modulo ufficiale FORMEDIL Lecce.
 *
 * Variabili attese (iniettate da PdfGenerator):
 * @var string $variante  DTL | ENTE
 * @var array  $dati      dati del modulo
 * @var string $token     token della richiesta
 * @var string $invioUrl  URL per la fase di invio (per il QR)
 * @var array  $schema    schema canonico (per le etichette delle opzioni)
 *
 * @package Formedil\Moduli
 */

use Formedil\Moduli\Pdf\Html;

$isEnte = ($variante === 'ENTE');
$opt = $schema['options'] ?? [];
$d = static fn(string $k, $def = '') => $dati[$k] ?? $def;

// Tipi corso selezionati.
$tipiSel = is_array($d('tipi_corso')) ? $d('tipi_corso') : [];
$strumentiSel = is_array($d('strumenti_verifica')) ? $d('strumenti_verifica') : [];
$giornate = is_array($d('giornate')) ? $d('giornate') : [];
$partecipanti = is_array($d('partecipanti')) ? $d('partecipanti') : [];
$docenti = is_array($d('docenti')) ? $d('docenti') : [];
$privacy = $d('privacy');
?>
<style>
  body { font-family: sans-serif; font-size: 9.5pt; color: #111; line-height: 1.4; }
  h1.tit { font-size: 11pt; text-align: right; margin: 0 0 2pt; }
  .dest { text-align: right; font-size: 9pt; margin-bottom: 10pt; }
  .oggetto { font-weight: bold; margin: 8pt 0; }
  .sec-title { font-weight: bold; margin: 8pt 0 3pt; }
  .small { font-size: 8.5pt; }
  .muted { color: #444; }
  p { margin: 4pt 0; }
  table { width: 100%; border-collapse: collapse; }
  table.grid th, table.grid td { border: 0.5pt solid #888; padding: 2pt 4pt; font-size: 8.5pt; }
  table.grid th { background: #f0f0f0; text-align: left; }
  .check { font-size: 11pt; }
  ul { margin: 3pt 0; padding-left: 14pt; }
  li { margin: 2pt 0; }
  .firma-box { border-top: 0.5pt solid #333; padding-top: 3pt; text-align: center; font-size: 8pt; }
  .token-box { border: 0.7pt solid #333; padding: 6pt; margin-top: 12pt; }
  .token-code { font-family: monospace; font-size: 12pt; font-weight: bold; letter-spacing: 1pt; }
</style>

<div class="dest">
  Spett.le Organismo Paritetico Settore Costruzioni<br>
  <strong>FORMEDIL LECCE</strong><br>
  Via Belgio – 73100 Lecce
</div>

<p class="oggetto">
  Oggetto: richiesta collaborazione – Art. 37 comma 12 D.Lgs 81/2008 s.m.i. –
  Accordo Stato Regioni del 17/04/2025
</p>

<?php if ($isEnte): ?>
  <p>
    Il sottoscritto <strong><?= Html::val($d('sottoscritto_nome')) ?></strong>
    nato a <?= Html::val($d('sottoscritto_nato_a')) ?> il <?= Html::date($d('sottoscritto_nato_il')) ?>,
    Rappresentante legale dell'Ente di Formazione <strong><?= Html::val($d('ente_nome')) ?></strong>
    con sede legale in <?= Html::val($d('ente_sede_legale')) ?>, via <?= Html::val($d('ente_via')) ?>,
    telefono <?= Html::val($d('ente_telefono')) ?>, email <?= Html::val($d('ente_email')) ?>,
    PEC <?= Html::val($d('ente_pec')) ?>, in qualità di SOGGETTO FORMATORE, munito di specifico
    mandato del datore di lavoro <strong><?= Html::val($d('mandato_datore')) ?></strong>
    dell'impresa <strong><?= Html::val($d('impresa_nome')) ?></strong> con P.I. <?= Html::val($d('impresa_piva')) ?>,
    iscritta alla Cassa Edile di <?= Html::val($d('impresa_cassa_edile_nome')) ?> al n. <?= Html::val($d('impresa_cassa_edile_n')) ?>,
    iscritta all'Edil Cassa di <?= Html::val($d('impresa_edil_cassa_nome')) ?> n. <?= Html::val($d('impresa_edil_cassa_n')) ?>,
    Codice ATECO <?= Html::val($d('impresa_ateco')) ?>,
  </p>
<?php else: ?>
  <p>
    Il sottoscritto <strong><?= Html::val($d('sottoscritto_nome')) ?></strong>
    nato a <?= Html::val($d('sottoscritto_nato_a')) ?> il <?= Html::date($d('sottoscritto_nato_il')) ?>,
    datore di lavoro dell'azienda <strong><?= Html::val($d('azienda_nome')) ?></strong>
    con sede legale in <?= Html::val($d('azienda_sede_legale')) ?>, via <?= Html::val($d('azienda_via')) ?>,
    telefono <?= Html::val($d('azienda_telefono')) ?>, email <?= Html::val($d('azienda_email')) ?>,
    PEC <?= Html::val($d('azienda_pec')) ?>, P.I. <?= Html::val($d('azienda_piva')) ?>,
    Codice Ateco <?= Html::val($d('azienda_ateco')) ?>,
    iscritta alla Cassa Edile di Lecce al N° <?= Html::val($d('azienda_cassa_edile_lecce_n')) ?>,
    iscritta alla Edil Cassa di <?= Html::val($d('azienda_edil_cassa_nome')) ?> al N° <?= Html::val($d('azienda_edil_cassa_n')) ?>,
    in qualità di SOGGETTO FORMATORE per i propri lavoratori
  </p>
<?php endif; ?>

<p>in coerenza all'art. 37 comma 12 del D.Lgs 81/2008 e s.m.i. e del PUNTO 2 PARTE II
Accordo Stato Regioni del 17/04/2025,</p>

<p class="oggetto" style="text-align:center;">CHIEDE A FORMEDIL LECCE LA COLLABORAZIONE</p>

<p>per l'organizzazione e l'erogazione dei seguenti corsi di formazione in materia di
salute e sicurezza:</p>

<?php foreach (($opt['tipiCorso'] ?? []) as $tc):
    $checked = in_array($tc['value'], $tipiSel, true); ?>
  <div>
    <span class="check"><?= Html::checkbox($checked) ?></span>
    <?= Html::esc($tc['label']) ?>
    <?php if (($tc['value'] ?? '') === 'specifica' && $checked): ?>
      — rischio: <strong><?= Html::optionLabel($opt['rischio'] ?? [], $d('rischio_specifica')) ?></strong>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<p>Il corso si svolgerà in modalità:
  <strong><?= Html::optionLabel($opt['modalita'] ?? [], $d('modalita')) ?></strong>
</p>

<p class="small muted">
  e a tale scopo, consapevole della responsabilità penale e delle sanzioni di cui agli
  artt. 75 e 76 del DPR 445/2000 in caso di dichiarazione mendace, e del fatto che, qualora
  emerga la non veridicità del contenuto delle dichiarazioni rese, la presente richiesta si
  riterrà automaticamente sospesa o revocata, fatto salvo ogni ulteriore profilo di
  responsabilità civile e penale,
</p>

<p class="oggetto">DICHIARA QUANTO SEGUE:</p>

<p class="sec-title">1. Organizzazione ed erogazione del corso</p>
<ul class="small">
  <li>Il corso riguarderà lavoratori operanti nel settore ATECO F (Costruzioni);</li>
  <?php if ($isEnte): ?>
    <li>Il Soggetto Formatore ha avuto specifico mandato dal/i datore/i di lavoro delle imprese indicate;</li>
    <li>Il Soggetto Formatore ha i requisiti previsti dal Punto 1 della Parte I dell'ASR del 17/04/2025;</li>
  <?php endif; ?>
  <li>Il corso sarà organizzato ed erogato secondo l'Accordo Stato Regioni del 17/04/2025;</li>
  <li>I docenti possiedono i requisiti di cui al Punto 2 della Parte I dell'ASR del 17/04/2025;</li>
  <?php if ($isEnte): ?>
    <li>Il Responsabile del Progetto Formativo ha i requisiti richiesti dall'ASR del 17/04/2025;</li>
  <?php endif; ?>
  <li>È stato verificato che gli eventuali partecipanti di origine straniera comprendono la lingua
      italiana, o sarà garantita la presenza di interprete/mediatore culturale;</li>
  <?php if (!$isEnte): ?>
    <li>Tutti i partecipanti sono lavoratori dell'azienda in oggetto.</li>
  <?php endif; ?>
</ul>

<p class="sec-title">2. Durata complessiva del corso</p>
<p>Dal <?= Html::date($d('durata_dal')) ?> al <?= Html::date($d('durata_al')) ?></p>

<?php $n = 2; ?>
<?php if ($isEnte): $n++; ?>
  <p class="sec-title"><?= $n ?>. Responsabile del Progetto Formativo</p>
  <table class="grid">
    <tr><th style="width:35%">Cognome Nome / Ragione sociale</th><td><?= Html::val($d('rpf_nome')) ?></td></tr>
    <tr><th>Indirizzo</th><td><?= Html::val($d('rpf_indirizzo')) ?></td></tr>
    <tr><th>Telefono</th><td><?= Html::val($d('rpf_telefono')) ?></td></tr>
    <tr><th>Email</th><td><?= Html::val($d('rpf_email')) ?></td></tr>
  </table>
<?php endif; ?>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Località dove si svolgerà il corso</p>
<table class="grid">
  <tr><th style="width:20%">Via</th><td><?= Html::val($d('sede_via')) ?></td></tr>
  <tr><th>Località</th><td><?= Html::val($d('sede_localita')) ?></td></tr>
</table>
<table class="grid" style="margin-top:4pt;">
  <tr><th style="width:34%">Data</th><th>Dalle ore</th><th>Alle ore</th></tr>
  <?php if ($giornate): foreach ($giornate as $g): ?>
    <tr>
      <td><?= Html::date($g['data'] ?? '') ?></td>
      <td><?= Html::time($g['ora_inizio'] ?? '') ?></td>
      <td><?= Html::time($g['ora_fine'] ?? '') ?></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td>—</td><td>—</td><td>—</td></tr>
  <?php endif; ?>
</table>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Programma esecutivo del corso</p>
<p style="border:0.5pt solid #888; padding:4pt; min-height:40pt;"><?= nl2br(Html::val($d('programma_esecutivo'), '')) ?></p>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Strumenti di verifica intermedi e/o finali</p>
<p>
  <?php foreach (($opt['strumentiVerifica'] ?? []) as $sv): ?>
    <span class="check"><?= Html::checkbox(in_array($sv['value'], $strumentiSel, true)) ?></span>
    <?= Html::esc($sv['label']) ?> &nbsp;&nbsp;
  <?php endforeach; ?>
</p>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Partecipanti al corso (massimo 30)</p>
<table class="grid">
  <tr><th style="width:8%">N.</th><th style="width:52%">Nome e cognome</th><th>Codice Fiscale</th></tr>
  <?php if ($partecipanti): foreach ($partecipanti as $i => $p): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= Html::val($p['nome_cognome'] ?? '') ?></td>
      <td><?= Html::val(strtoupper((string) ($p['codice_fiscale'] ?? ''))) ?></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td>1</td><td>—</td><td>—</td></tr>
  <?php endif; ?>
</table>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Nominativi dei docenti (massimo 4)</p>
<table class="grid">
  <tr><th style="width:8%">N.</th><th>Nome</th><th>Cognome</th><th>Data di nascita</th></tr>
  <?php if ($docenti): foreach ($docenti as $i => $doc): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= Html::val($doc['nome'] ?? '') ?></td>
      <td><?= Html::val($doc['cognome'] ?? '') ?></td>
      <td><?= Html::date($doc['data_nascita'] ?? '') ?></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td>1</td><td>—</td><td>—</td><td>—</td></tr>
  <?php endif; ?>
</table>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Si impegna, pena la decadenza della collaborazione, a:</p>
<ul class="small">
  <li>trasmettere la presente richiesta almeno 15 gg prima dell'erogazione del corso;</li>
  <li>rispettare le eventuali indicazioni e suggerimenti di FORMEDIL LECCE;</li>
  <li>informare tempestivamente FORMEDIL LECCE in caso di variazioni (annullamento, sede, orari, partecipanti);</li>
  <li>trasmettere, se richiesto ed entro 15 gg, il registro delle presenze e le risultanze delle verifiche.</li>
</ul>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Dichiara di essere a conoscenza che:</p>
<ul class="small">
  <li>FORMEDIL LECCE è un Organismo Paritetico del settore delle Costruzioni (ATECO F);</li>
  <li>FORMEDIL LECCE può effettuare verifiche in loco senza preavviso e revocare la collaborazione
      in caso di divieto/impossibilità di accesso o di dichiarazioni non veritiere.</li>
</ul>

<p><strong>Eventuali note o comunicazioni:</strong> <?= Html::val($d('note'), '') ?></p>

<p><strong>Allegati:</strong></p>
<ul class="small">
  <?php foreach (($schema['variants'][$variante]['allegati'] ?? []) as $a): ?>
    <li><?= Html::esc($a) ?></li>
  <?php endforeach; ?>
</ul>

<p>Sul sito www.formedillecce.it è presente l'informativa sulla privacy.</p>
<p>
  <span class="check"><?= Html::checkbox($privacy === 'autorizzo') ?></span> Autorizzo il trattamento dei dati &nbsp;&nbsp;
  <span class="check"><?= Html::checkbox($privacy === 'non_autorizzo') ?></span> NON Autorizzo il trattamento dei dati
</p>

<p>Data: <?= Html::date($d('durata_dal')) ? date('d/m/Y') : date('d/m/Y') ?></p>

<table style="margin-top:18pt;">
  <tr>
    <?php foreach (($schema['variants'][$variante]['firme'] ?? []) as $firma): ?>
      <td style="padding:0 10pt;">
        <div class="firma-box">Timbro e firma<br><?= Html::esc($firma) ?></div>
      </td>
    <?php endforeach; ?>
  </tr>
</table>

<div class="token-box">
  <table>
    <tr>
      <td style="vertical-align:middle;">
        <strong>Codice di invio documentazione</strong><br>
        <span class="token-code"><?= Html::esc($token) ?></span><br>
        <span class="small muted">
          Conserva questo codice. Dopo aver firmato digitalmente il PDF, vai su
          <?= Html::esc(parse_url($invioUrl, PHP_URL_HOST) ?: 'moduli.formedillecce.it') ?>
          → "Invia documentazione" e inserisci il codice per caricare il modulo firmato e gli allegati.
        </span>
      </td>
      <td style="width:90pt; text-align:right; vertical-align:middle;">
        <barcode code="<?= Html::esc($invioUrl) ?>" type="QR" class="barcode" size="0.9" error="M" />
      </td>
    </tr>
  </table>
</div>
