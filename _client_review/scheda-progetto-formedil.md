# FORMEDIL Lecce — Portale richieste di collaborazione Art. 37

---

## 1. SOMMARIO

FORMEDIL Lecce è l'organismo paritetico del settore delle costruzioni per la provincia di Lecce. Ogni impresa edile o ente di formazione che intende erogare formazione sulla sicurezza ai sensi dell'art. 37 comma 12 del D.Lgs 81/2008 deve inviargli una richiesta di collaborazione almeno quindici giorni prima del corso. La procedura era un modulo su una vecchia pagina ASP: si compilava, si stampava, si firmava digitalmente e si spediva a una casella email dell'ufficio sicurezza, dove le pratiche si accumulavano senza stato né tracciabilità.

Abbiamo realizzato un portale che copre l'intero ciclo. Il richiedente sceglie il proprio profilo — impresa edile oppure ente di formazione — compila un percorso guidato a step e ottiene subito un PDF con un token identificativo stampato sopra. Lo firma con la propria firma digitale e lo ricarica dal portale inserendo il token, insieme agli allegati previsti. Il sistema notifica FORMEDIL, che lavora le pratiche da un pannello dedicato con stati, cronologia e riscontro al richiedente.

L'architettura è headless: WordPress espone solo API REST, l'interfaccia è una single page application React. Un unico schema JSON descrive campi, validazioni e varianti ed è condiviso da frontend e backend, così ogni modifica al modulo si fa in un punto solo.

*(200 parole)*

---

## 2. CARD DI ANTEPRIMA

Portale per la richiesta di collaborazione Art. 37 all'organismo paritetico FORMEDIL Lecce. Abbiamo sostituito il modulo da stampare e spedire via email con un percorso guidato che genera il PDF, lo lega a un token e ne gestisce il ricarico firmato digitalmente. WordPress headless con API REST, frontend React, pannello di gestione con stati e notifiche automatiche a richiedente e ufficio.

*(63 parole)*

---

## 3. LA SFIDA

Il modulo Art. 37 non è un form di contatto: raccoglie l'anagrafica completa dell'impresa, i tipi di corso richiesti fra sette possibili, il programma didattico, gli strumenti di verifica, fino a 25 partecipanti con codice fiscale e fino a 4 docenti, in due varianti normative distinte — impresa edile che forma direttamente i propri lavoratori, oppure ente di formazione che agisce per conto di una o più imprese. Nella versione precedente tutto questo viveva in una pagina ASP legacy e in un allegato Word da stampare: il richiedente sbagliava campi, l'ufficio riceveva PDF via email senza modo di sapere quali pratiche fossero complete, in verifica o già evase.

La difficoltà principale era la firma digitale. La normativa richiede il documento firmato, ma la firma avviene fuori dal portale, sul computer del richiedente: il sistema doveva quindi interrompersi a metà del flusso e saper riconoscere, giorni dopo, il documento che torna indietro — senza account, senza password, senza email.

*(158 parole)*

---

## 4. LA SOLUZIONE

Dalla home l'utente sceglie fra due porte: impresa edile o ente di formazione. Il percorso di compilazione è diviso in step, mostra solo i campi pertinenti alla variante scelta, valida codice fiscale e partita IVA mentre si scrive, salva automaticamente la bozza in locale e consente di incollare l'elenco dei partecipanti da Excel invece di digitarlo riga per riga. Provincia, comune e CAP si compilano a catena da un dataset caricato solo quando serve. Al termine il server produce il PDF definitivo con il token stampato sopra; l'utente lo firma con la propria firma digitale e torna sul portale, digita il token e carica il file firmato più i documenti di identità richiesti.

Il backend è un plugin WordPress con namespace REST dedicato, che genera i PDF server-side e conserva allegati e documenti in una cartella protetta fuori dalla libreria media. Un solo schema JSON, condiviso fra frontend e backend, definisce campi, varianti e regole di validazione: le stesse regole valgono sul browser e sul server, e cambiare il modulo significa modificare un file. Gli endpoint pubblici sono protetti da rate limiting per IP, anche contro l'enumerazione dei token, e ogni passaggio finisce in un registro di audit.

Oggi l'ufficio sicurezza vede le richieste in un elenco filtrabile per stato, apre il dettaglio, scarica modulo firmato e allegati e invia l'esito al richiedente dal pannello, con anteprima del messaggio. Le email partono da sole a ogni passaggio, e ogni pratica ha una cronologia di chi ha fatto cosa e quando.

*(250 parole)*

---

## 5. TECNOLOGIE

- **WordPress (headless)** — fa da backend e da database della piattaforma: espone solo API REST sul namespace `/wp-json/formedil/v1/`, mentre l'interfaccia pubblica è interamente React. Il cliente resta su un ambiente che il suo hosting già supporta.
- **PHP 8 con autoloading PSR-4** — il plugin è organizzato per responsabilità (REST, servizi, PDF, storage, validazione, audit), così ogni modifica futura tocca un solo punto del codice.
- **React 18 + Vite** — single page application per la compilazione: nessun ricaricamento di pagina fra uno step e l'altro, build ottimizzata per il caricamento rapido anche da cantiere in mobilità.
- **React Router** — gestisce le tre aree del portale (home a due porte, nuova richiesta, invio documentazione) come URL condivisibili.
- **mPDF + mpdf/qrcode** — genera server-side il PDF ufficiale conforme al modulo cartaceo, con il token e il QR code per tornare alla pagina di invio.
- **Schema JSON condiviso** — unica fonte di verità per campi, varianti, step e validazioni: elimina il rischio che frontend e backend accettino dati diversi.
- **Token opaco al posto dell'account** — lega il PDF firmato alla pratica originale senza obbligare imprese ed enti a registrarsi.
- **Rate limiting su transient WordPress + audit log su tabella dedicata** — protegge gli endpoint pubblici da abusi ed enumerazione dei token e lascia traccia di ogni cambio di stato.
- **wp_mail con template HTML e configurazione SMTP da pannello** — email transazionali a richiedente e ufficio, con mittente e destinatari modificabili dal cliente senza toccare il codice.
- **CSS Custom Properties** — palette e tipografia centralizzate: il restyling sul brand arancione e la palette blue/grey del pannello admin sono stati fatti senza riscrivere i componenti.

---

## 6. TEMPISTICA

| Fase | Attività | Durata |
|---|---|---|
| S0 | Analisi del modulo cartaceo e della pagina ASP legacy, schema dati unificato delle due varianti, scaffold backend e frontend | 2 giorni |
| S1 | Backend richieste: modello dati, endpoint di creazione, generazione PDF server-side, token, validazioni CF/P.IVA | 4 giorni |
| S2 | Percorso guidato frontend schema-driven, validazioni inline, salvataggio bozza, incolla partecipanti da Excel, pagina esito | 4 giorni |
| S3 | Firma e ricarico: pagina token, upload PDF firmato e allegati, notifica a FORMEDIL | 3 giorni |
| S4 | Pannello di gestione: elenco, filtri, stati, download documenti, cronologia | 3 giorni |
| S7 | Email transazionali a richiedente e ufficio, pagina impostazioni SMTP | 2 giorni |
| S8 | Hardening: rate limiting, limiti sugli allegati, audit log | 2 giorni |
| S9 | Deploy in produzione su hosting del cliente (WordPress in sottocartella, SPA in root) | 2 giorni |
| S10 | Ottimizzazione performance frontend (immagini WebP, caricamento lazy dei dataset) | 1 giorno |
| S11 | Revisione post-collaudo con il cliente: ristrutturazione della variante Ente con imprese multiple, nuovo elenco tipi di corso, campo ATECO | 4 giorni |
| S12 | Download del PDF generato dal pannello, rifiniture | 1 giorno |
| **Totale** | **11 giugno – 28 luglio 2026** | **~7 settimane** |

---

### ⚠️ Dati da confermare

Le durate per sprint qui sopra sono **stimate dalla cronologia dei commit**, non da un consuntivo reale: confermale o correggile. Mancano inoltre, se vuoi inserirli:

- volumi (richieste gestite dal go-live, imprese registrate)
- dimensione del team e ruoli
- eventuale riferimento pubblicabile all'URL del portale
