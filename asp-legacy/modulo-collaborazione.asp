<% id_url = 31 : portale_url = "https://gestionale.formedillecce.it" %>
<!--#include virtual="inc_top.asp"-->

<!--
  Pagina "Richiesta Collaborazione" - aggiornata luglio 2026.

  Il modulo non si compila piu' qui: la procedura e' stata spostata sul portale
  gestionale.formedillecce.it, dove la richiesta viene compilata, generata in
  PDF, firmata (a mano o digitalmente) e ricaricata dal richiedente stesso.

  Rimossi da questo file: gestione del POST (validazione, salvataggio a
  database, invio email, generazione PDF con Persits.Pdf, captcha), il form a
  quattro passi e il JavaScript collegato.

  Titolo e testo introduttivo arrivano dal CMS (id_url 31): si modificano dal
  pannello del sito, non da qui.

  NON e' stato toccato il resto del vecchio sistema: collaborazioni.asp e
  modulo-collaborazione-pdf.asp continuano a funzionare, quindi le richieste
  gia' ricevute restano consultabili e stampabili.

  ATTENZIONE: non riformattare questo file con Prettier o strumenti simili.
  Non conoscono l'ASP e mandano a capo il codice dentro i delimitatori di
  script, rompendolo. Per sicurezza le istruzioni della prima riga sono separate
  da due punti, cosi' restano valide anche se qualcuno le unisce su una riga.

  In questo commento non vanno mai scritti i delimitatori ASP: il server li
  interpreta anche dentro i commenti HTML, che per lui non esistono.

  Copia della versione precedente: repository del progetto, cartella asp-legacy.
  Non lasciarne copie .bak o .txt sul server: verrebbero servite in chiaro.
-->

<div class="col-md-12 schermata">
  <div style="text-align: center">
    <h1 style="margin-bottom: 0; margin: 0px auto"><%=h1%></h1>
  </div>

  <div class="col-md-12" style="padding: 0; margin: 0; font-size: 14px">
    <%=corpo%>
  </div>

  <!-- Richiamo al nuovo portale -->
  <div class="col-md-12" style="padding: 0; margin: 26px 0 10px">
    <div
      style="
        border: 1px solid #e2e2e2;
        border-top: 4px solid #d35d13;
        border-radius: 6px;
        padding: 26px 24px;
        background: #fbfbfb;
      "
    >
      <h3 style="color: #222; margin: 0 0 10px; font-size: 20px">
        La richiesta si compila online
      </h3>

      <p
        style="font-size: 14px; line-height: 1.6; margin: 0 0 18px; color: #444"
      >
        Dal portale dedicato puoi compilare la richiesta di collaborazione e
        scaricare il PDF gi&agrave; pronto.
        <br />
        Una volta firmato &mdash; a mano oppure con firma digitale &mdash; lo
        ricarichi sullo stesso portale: non &egrave; pi&ugrave; necessario
        inviarlo via email.
        <br />
        A ogni richiesta viene assegnato un
        <strong>codice pratica</strong> con cui riprendere l'invio dei documenti
        anche in un secondo momento.
      </p>

      <!-- Pulsanti in tabella: reggono anche senza il CSS del tema -->
      <table
        role="presentation"
        cellpadding="0"
        cellspacing="0"
        style="border: 0"
      >
        <tr>
          <td style="padding: 0 12px 12px 0">
            <a
              href="<%=portale_url%>/nuova"
              target="_blank"
              rel="noopener"
              style="
                display: inline-block;
                background: #d35d13;
                color: #ffffff;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                padding: 14px 26px;
                border-radius: 6px;
              "
              >NUOVA RICHIESTA
            </a>
          </td>
          <td style="padding: 0 0 12px 0">
            <a
              href="<%=portale_url%>/invio"
              target="_blank"
              rel="noopener"
              style="
                display: inline-block;
                background: #ffffff;
                color: #d35d13;
                border: 2px solid #d35d13;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 6px;
              "
              >INVIA i documenti firmati</a
            >
          </td>
        </tr>
      </table>

      <p style="font-size: 13px; margin: 14px 0 0">
        I collegamenti si aprono in una nuova scheda. Il secondo pulsante serve
        a chi ha gi&agrave; compilato la richiesta e deve caricare il modulo
        firmato: tieni a portata il codice pratica ricevuto per email.
      </p>
    </div>
  </div>
</div>

<!--#include virtual="inc_bot.asp"-->
