# Configurazione email — gestionale.formedillecce.it

Guida operativa per far partire le due email automatiche del gestionale
(«pratica inserita» con il PDF allegato e «documenti ricevuti»).

Ultimo aggiornamento: 27 luglio 2026.

---

## 1. Quadro di partenza

Il sito storico in ASP manda le notifiche da `no-reply@formedillecce.it`, una
casella reale con SMTP autenticato. Il nuovo gestionale userà **la stessa
casella**: i record DNS del dominio la conoscono già e la sua reputazione è
consolidata, quindi le email hanno più probabilità di arrivare in posta in
arrivo invece che in spam.

Le credenziali si trovano nel vecchio codice, in `cms/Connections/email.asp`,
nel blocco `iConf_noreply` (variabili `sendusername` e `sendpassword`).

> **Nota di sicurezza.** Quelle password sono in chiaro dentro un file nella
> webroot del sito ASP. Oggi non sono esposte perché i file `.asp` vengono
> eseguiti e non mostrati, ma una copia rinominata (`email.asp.bak`,
> `email.txt`) diventerebbe leggibile da chiunque. Quando c'è tempo, vale la
> pena spostare quel file fuori dalla webroot.

---

## 2. File da caricare

### 2.1 Plugin — via FTP in `cms/wp-content/plugins/formedil-moduli/`

| File | Cosa cambia |
|---|---|
| `src/Rest/RestController.php` | corregge il dominio nei link di invio (email, PDF, QR) |
| `templates/pdf-richiesta.php` | stesso fix nel testo del PDF |
| `src/Service/Mailer.php` | supporto a più caselle FORMEDIL in Cc |

Non serve disattivare e riattivare il plugin: nessuna tabella nuova.

### 2.2 Configurazione — via FTP in `cms/wp-content/mu-plugins/`

Caricare `formedil-config.php` (si trova in `dist-deploy/mu-plugins/`).

Se la cartella `mu-plugins` non esiste, crearla: il nome va scritto
esattamente così, tutto minuscolo. I «must-use plugin» si attivano da soli,
non compaiono nell'elenco plugin e non si possono disattivare per errore.

Il file contiene già:

- mittente `no-reply@formedillecce.it`
- Cc a `responsabileareasicurezza@formedillecce.it` e `direttore@formedillecce.it`

Per cambiare i destinatari in futuro basta modificare quella riga: sono
separati da virgola e il codice del plugin non va toccato.

---

## 3. WP Mail SMTP

WordPress da solo usa la funzione `mail()` di PHP, che sull'hosting condiviso
Aruba parte senza autenticazione e finisce spesso in spam. Serve un plugin che
invii tramite SMTP autenticato.

### 3.1 Installazione

`cms/wp-admin` → Plugin → Aggiungi nuovo → cerca **WP Mail SMTP** (di WPForms)
→ Installa → Attiva. La versione gratuita basta: salta pure la procedura
guidata iniziale e vai su **WP Mail SMTP → Impostazioni**.

### 3.2 Parametri

**Sezione «Posta»**

| Campo | Valore |
|---|---|
| Indirizzo email «Da» | `no-reply@formedillecce.it` |
| Forza indirizzo email «Da» | **attivo** |
| Nome «Da» | `FORMEDIL Lecce` |
| Mailer | **Altro SMTP** |

**Sezione «Altro SMTP»**

| Campo | Valore |
|---|---|
| Host SMTP | `smtps.aruba.it` |
| Crittografia | **SSL** |
| Porta SMTP | `465` |
| Autenticazione | **attiva** |
| Nome utente SMTP | `no-reply@formedillecce.it` |
| Password SMTP | quella in `iConf_noreply` |

Il vecchio sito ASP usa `smtp.formedillecce.it` sulla porta 25 senza
crittografia. Ha sempre funzionato dal server Windows, ma conviene non
replicarlo qui: la porta 25 in uscita è spesso filtrata sugli hosting Linux e
manda la password in chiaro sulla rete. La 465 in SSL usa le stesse credenziali
ed è cifrata.

### 3.3 Password fuori dal database (opzionale, consigliato)

WP Mail SMTP salva la password nel database in chiaro. Per evitarlo, si può
definirla in `cms/wp-config.php` sopra la riga
`/* That's all, stop editing! */`:

```php
define( 'WPMS_ON', true );
define( 'WPMS_SMTP_PASS', 'la-password-della-casella' );
```

Fatto questo, il campo password nel pannello risulta bloccato e non è più
leggibile da chi entra in wp-admin.

---

## 4. Verifica

### 4.1 Test del plugin

**WP Mail SMTP → Strumenti → Test email**: invia a un tuo indirizzo. Deve
comparire la conferma verde. Se fallisce, il messaggio di errore dice quasi
sempre la causa (credenziali errate, porta chiusa, mittente non autorizzato).

### 4.2 Test reale end-to-end

Dal sito pubblico crea una richiesta di prova con un indirizzo email tuo, poi
controlla:

- [ ] arriva l'email **«Richiesta registrata»** con il **PDF allegato**
- [ ] il pulsante «Firma e carica i documenti» apre
      `https://gestionale.formedillecce.it/invio/FME-...`
- [ ] il **QR code stampato sul PDF** porta allo stesso indirizzo
- [ ] le due caselle FORMEDIL ricevono la copia in Cc
- [ ] caricando il PDF firmato arriva la seconda email **«Documenti ricevuti»**
- [ ] la richiesta compare in wp-admin → FORMEDIL con lo stato aggiornato

### 4.3 Se qualcosa non arriva

Le email non bloccano mai il salvataggio della pratica: se l'invio fallisce, la
richiesta resta valida e l'errore finisce nel log. Per leggerlo, attiva la
diagnostica decommentando il blocco finale di `formedil-config.php` e, in
`wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Gli errori compaiono in `cms/wp-content/debug.log`. **Ricordarsi di rimettere
`WP_DEBUG` a `false` quando hai finito**: con il debug attivo il log cresce
senza limiti.

---

## 5. Riepilogo indirizzi

| Ruolo | Indirizzo |
|---|---|
| Mittente notifiche | `no-reply@formedillecce.it` |
| Cc interna FORMEDIL | `responsabileareasicurezza@formedillecce.it`, `direttore@formedillecce.it` |
| Destinatario principale | l'email indicata dal richiedente nel form |

Nella variante ENTE, se manca l'email dell'impresa il sistema ripiega
sull'email dell'ente formatore e, in mancanza anche di quella, sulla prima
impresa in elenco. Se non c'è nessun indirizzo, la notifica va comunque alle
caselle FORMEDIL.
