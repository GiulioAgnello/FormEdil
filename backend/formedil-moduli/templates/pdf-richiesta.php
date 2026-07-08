<?php
/**
 * Template HTML del PDF di richiesta collaborazione (IMPRESA / ENTE).
 * Allineato allo schema v2.
 *
 * Lo stile sta in templates/pdf-richiesta.css (caricato a parte da PdfGenerator).
 * Qui c'è SOLO struttura e dati.
 *
 * Variabili attese (iniettate da PdfGenerator):
 * @var string $variante  IMPRESA | ENTE
 * @var array  $dati      dati del modulo
 * @var string $token     token della richiesta
 * @var string $invioUrl  URL per la fase di invio (per il QR)
 * @var array  $schema    schema canonico (per le etichette delle opzioni)
 * @var string $logoSrc   path/URL del logo per il letterhead ('' se assente)
 *
 * @package Formedil\Moduli
 */

use Formedil\Moduli\Pdf\Html;

$isEnte = ($variante === 'ENTE');
$opt = $schema['options'] ?? [];
$d = static fn(string $k, $def = '') => $dati[$k] ?? $def;

$tipoSel = is_string($d('tipo_corso')) ? $d('tipo_corso') : '';
$strumentiSel = is_array($d('strumenti_verifica')) ? $d('strumenti_verifica') : [];
$giornate = is_array($d('giornate')) ? $d('giornate') : [];
$partecipanti = is_array($d('partecipanti')) ? $d('partecipanti') : [];
$docenti = is_array($d('docenti')) ? $d('docenti') : [];
$privacy = $d('privacy');
$logoSrc = $logoSrc ?? '';

// Testo iscrizione Casse Edili dal radio + campi condizionati.
$iscr = (string) $d('iscrizione_cassa');
if ($iscr === 'cassa_edile') {
    $iscrTxt = 'iscritta alla Cassa Edile di ' . Html::val($d('cassa_edile_provincia')) . ' al n. ' . Html::val($d('cassa_edile_numero'));
} elseif ($iscr === 'edil_cassa') {
    $iscrTxt = 'iscritta alla Edil Cassa di ' . Html::val($d('edil_cassa_provincia')) . ' al n. ' . Html::val($d('edil_cassa_numero'));
} else {
    $iscrTxt = 'non iscritta a Casse Edili';
}

// Specificazioni legate alla tipologia di corso.
$specMap = [
    'specifico'      => 'rischio: ' . Html::optionLabel($opt['rischio'] ?? [], $d('rischio_specifico')),
    'figure_sistema' => 'figura: ' . Html::val($d('spec_figure')),
    'mansione'       => 'mansione: ' . Html::val($d('spec_mansione')),
    'addestramento'  => Html::val($d('spec_addestramento')),
    'attrezzature'   => 'attrezzatura: ' . Html::val($d('spec_attrezzature')),
    'altro'          => Html::val($d('spec_altro')),
];
?>

<table class="letterhead">
  <tr>
    <td class="logo">
      <?php if ($logoSrc !== ''): ?>
        <img src="<?= Html::esc($logoSrc) ?>" alt="FORMEDIL Lecce">
      <?php else: ?>
        <strong style="color:#D35D13; font-size:15pt;">FORMEDIL</strong><br>
        <span class="small muted">Ente Unico Formazione e Sicurezza · Lecce</span>
      <?php endif; ?>
    </td>
    <td class="dest">
      Spett.le Organismo Paritetico Settore Costruzioni<br>
      <strong>FORMEDIL LECCE</strong><br>
      Via Belgio – 73100 Lecce
    </td>
  </tr>
</table>

<p class="oggetto">
  Oggetto: richiesta collaborazione – Art. 37 comma 12 D.Lgs 81/2008 s.m.i. –
  Accordo Stato Regioni del 17/04/2025
</p>

<p class="intro">
  Il sottoscritto <strong><?= Html::val($d('datore_nome')) ?> <?= Html::val($d('datore_cognome')) ?></strong>,
  in riferimento all'azienda/impresa <strong><?= Html::val($d('azienda_ragione_sociale')) ?></strong>,
  esercente l'attività di <?= Html::val($d('azienda_esercente')) ?>,
  con sede legale in <?= Html::val($d('azienda_indirizzo')) ?>, <?= Html::luogo($d('azienda_sede')) ?>,
  telefono <?= Html::val($d('azienda_telefono')) ?>, email <?= Html::val($d('azienda_email')) ?>,
  PEC <?= Html::val($d('azienda_pec')) ?>, P.IVA <?= Html::val($d('azienda_piva')) ?>,
  Codice ATECO <?= Html::val($d('azienda_ateco')) ?>, <?= $iscrTxt ?>,
  <?php if ($isEnte): ?>
    per il tramite del Soggetto Organizzatore
    <strong><?= Html::val($d('org_ragione_sociale')) ?></strong>
    (indirizzo <?= Html::val($d('org_indirizzo')) ?>, tel. <?= Html::val($d('org_telefono')) ?>,
    email <?= Html::val($d('org_email')) ?>, PEC <?= Html::val($d('org_pec')) ?>),
    in qualità di SOGGETTO FORMATORE munito di specifico mandato del datore di lavoro
  <?php else: ?>
    in qualità di SOGGETTO FORMATORE per i propri lavoratori
  <?php endif; ?>
  e in coerenza all'art. 37 comma 12 del D.Lgs 81/2008 e s.m.i. e del PUNTO 2 PARTE II
  Accordo Stato Regioni del 17/04/2025,
</p>

<p class="chiede">CHIEDE A FORMEDIL LECCE LA COLLABORAZIONE</p>

<p>per l'organizzazione e l'erogazione del seguente corso di formazione in materia di
salute e sicurezza (riferimento normativo: <?= Html::val($d('riferimento_normativo')) ?>):</p>

<div class="section">
<?php foreach (($opt['tipiCorso'] ?? []) as $tc):
    $checked = ($tc['value'] ?? '') === $tipoSel; ?>
  <div class="choice">
    <span class="check"><?= Html::checkbox($checked) ?></span>
    <?= Html::esc($tc['label']) ?>
    <?php if ($checked && !empty($specMap[$tc['value']] ?? '')): ?>
      — <strong><?= $specMap[$tc['value']] ?></strong>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<p>Il corso si svolgerà in modalità:
  <strong><?= Html::optionLabel($opt['modalita'] ?? [], $d('modalita')) ?></strong>
</p>

<p class="small muted">
  e a tale scopo, consapevole della responsabilità penale e delle sanzioni di cui agli
  artt. 75 e 76 del DPR 445/2000 in caso di dichiarazione mendace,
</p>

<p class="dichiara">DICHIARA QUANTO SEGUE:</p>

<p class="sec-title">1. Organizzazione ed erogazione del corso</p>
<ul class="small">
  <?php if ($isEnte): ?>
    <li>Il corso riguarderà lavoratori del settore ATECO F (Costruzioni);</li>
    <li>Il Soggetto Formatore ha avuto specifico mandato dal/i datore/i di lavoro della/e impresa/e indicate;</li>
    <li>Il corso sarà organizzato ed erogato secondo quanto stabilito dall'Accordo Stato Regioni del 17/04/2025;</li>
    <li>Il Soggetto Formatore ha i requisiti previsti dal Punto 1 della Parte I dell'ASR del 17/04/2025;</li>
    <li>I docenti sono in possesso dei requisiti di cui al Punto 2 della Parte I dell'ASR del 17/04/2025;</li>
    <li>Il Responsabile del Progetto Formativo ha i requisiti richiesti dall'ASR del 17/04/2025;</li>
    <li>È stato verificato dal Soggetto Formatore e/o dal datore di lavoro dell'impresa che gli eventuali
        partecipanti di origine straniera hanno una comprensione e conoscenza della lingua italiana adeguata
        e sufficiente a seguire e comprendere le tematiche trattate, o in alternativa sarà assicurata la
        presenza di un interprete o di un mediatore culturale.</li>
  <?php else: ?>
    <li>Il corso riguarderà lavoratori dell'azienda e operanti nel settore di attività ATECO F (Costruzioni);</li>
    <li>Il corso sarà organizzato ed erogato secondo quanto stabilito dall'Accordo Stato Regioni del 17/04/2025;</li>
    <li>I docenti sono in possesso dei requisiti di cui al Punto 2 della Parte I dell'ASR del 17/04/2025;</li>
    <li>È stato verificato che gli eventuali partecipanti di origine straniera hanno una comprensione e
        conoscenza della lingua italiana adeguata e sufficiente a seguire e comprendere le tematiche trattate,
        o in alternativa sarà assicurata la presenza di un interprete o di un mediatore culturale;</li>
    <li>Tutti i partecipanti al corso sono lavoratori dell'azienda in oggetto;</li>
    <li>Nel caso in cui il datore di lavoro abbia assunto anche la funzione di docente, è in possesso dei
        requisiti per lo svolgimento diretto dei compiti del servizio di prevenzione e protezione.</li>
  <?php endif; ?>
</ul>

<p class="sec-title">2. Durata complessiva del corso</p>
<p>Dal <?= Html::date($d('durata_dal')) ?> al <?= Html::date($d('durata_al')) ?></p>

<?php $n = 2; ?>
<?php if ($isEnte): $n++; ?>
  <div class="section">
  <p class="sec-title"><?= $n ?>. Responsabile del Progetto Formativo</p>
  <table class="grid">
    <tbody>
      <tr><th>Cognome Nome / Ragione sociale</th><td><?= Html::val($d('rpf_nome')) ?></td></tr>
      <tr><th>Indirizzo</th><td><?= Html::val($d('rpf_indirizzo')) ?></td></tr>
      <tr><th>Telefono</th><td><?= Html::val($d('rpf_telefono')) ?></td></tr>
      <tr><th>Email</th><td><?= Html::val($d('rpf_email')) ?></td></tr>
    </tbody>
  </table>
  </div>
<?php endif; ?>

<?php $n++; ?>
<div class="section">
<p class="sec-title"><?= $n ?>. Località dove si svolgerà il corso</p>
<table class="grid">
  <tbody>
    <tr><th>Sede svolgimento</th><td><?= Html::val($d('sede_indirizzo')) ?></td></tr>
    <tr><th>Provincia / Comune / CAP</th><td><?= Html::luogo($d('sede_luogo')) ?></td></tr>
  </tbody>
</table>
<table class="grid" style="margin-top:4pt;">
  <thead>
    <tr><th style="width:34%">Data</th><th>Dalle ore</th><th>Alle ore</th></tr>
  </thead>
  <tbody>
  <?php if ($giornate): foreach ($giornate as $g): ?>
    <tr>
      <td><?= Html::date($g['data'] ?? '') ?></td>
      <td><?= Html::time($g['ora_inizio'] ?? '') ?></td>
      <td><?= Html::time($g['ora_fine'] ?? '') ?></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td>—</td><td>—</td><td>—</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Progetto formativo di dettaglio</p>
<p class="freebox"><?= nl2br(Html::val($d('progetto_formativo'), '')) ?></p>

<?php $n++; ?>
<div class="section">
<p class="sec-title"><?= $n ?>. Strumenti di verifica intermedi e/o finali</p>
<p>
  <?php foreach (($opt['strumentiVerifica'] ?? []) as $sv): ?>
    <span class="check"><?= Html::checkbox(in_array($sv['value'], $strumentiSel, true)) ?></span>
    <?= Html::esc($sv['label']) ?> &nbsp;&nbsp;
  <?php endforeach; ?>
</p>
</div>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Partecipanti al corso (massimo 25)</p>
<table class="grid">
  <thead>
    <tr><th style="width:8%">N.</th><th style="width:34%">Nome</th><th style="width:34%">Cognome</th><th>Codice Fiscale</th></tr>
  </thead>
  <tbody>
  <?php if ($partecipanti): foreach ($partecipanti as $i => $p): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= Html::val($p['nome'] ?? '') ?></td>
      <td><?= Html::val($p['cognome'] ?? '') ?></td>
      <td><?= Html::val($p['codice_fiscale'] ?? '') ?></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td>1</td><td>—</td><td>—</td><td>—</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Nominativi dei docenti (massimo 4)</p>
<table class="grid">
  <thead>
    <tr>
      <th style="width:8%">N.</th><th style="width:34%">Nome</th><th style="width:34%">Cognome</th><th>Data di nascita</th>
    </tr>
  </thead>
  <tbody>
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
  </tbody>
</table>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Si impegna, pena la decadenza della collaborazione, a:</p>
<ul class="small section">
  <li>trasmettere la presente richiesta almeno 15 gg prima dell'erogazione del corso;</li>
  <li>rispettare le eventuali indicazioni e suggerimenti di FORMEDIL LECCE;</li>
  <li>informare tempestivamente FORMEDIL LECCE in caso di variazioni (annullamento, sede, orari, partecipanti);</li>
  <li>trasmettere, se richiesto ed entro 15 gg, il registro delle presenze e le risultanze delle verifiche.</li>
</ul>

<?php $n++; ?>
<p class="sec-title"><?= $n ?>. Dichiara di essere a conoscenza che:</p>
<ul class="small section">
  <?php if (!$isEnte): ?>
    <li>FORMEDIL LECCE è un Organismo Paritetico del settore delle Costruzioni (ATECO F);</li>
  <?php endif; ?>
  <li>Durante i giorni di erogazione del corso, FORMEDIL LECCE si riserva la facoltà di effettuare, senza
      necessità di preavviso, verifiche &quot;in loco&quot; sul regolare svolgimento delle attività formative.
      In caso di divieto o impossibilità di accesso, o qualora si accerti che anche una sola delle
      dichiarazioni rese non corrisponde, parzialmente o interamente, al vero, FORMEDIL LECCE si riserva
      la facoltà di revocare la collaborazione richiesta.</li>
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

<p>Data: <?= date('d/m/Y') ?></p>

<table class="firme section">
  <tr>
    <?php foreach (($schema['variants'][$variante]['firme'] ?? []) as $firma): ?>
      <td>
        <div class="firma-box">Timbro e firma<br><?= Html::esc($firma) ?></div>
      </td>
    <?php endforeach; ?>
  </tr>
</table>

<div class="token-box">
  <table>
    <tr>
      <td style="vertical-align:middle;">
        <span class="label">Codice di invio documentazione</span><br>
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
