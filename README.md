# FORMEDIL Moduli — Richiesta collaborazione Art. 37

Digitalizzazione dei moduli di richiesta collaborazione a **FORMEDIL Lecce**
(Art. 37 c.12 D.Lgs 81/2008 · ASR 17/04/2025), nelle due varianti:

- **DTL** — datore di lavoro che forma direttamente i propri dipendenti
- **ENTE** — ente di formazione con mandato del datore di lavoro

Il portale gira su un sottodominio dedicato (es. `moduli.formedillecce.it`):
l'utente compila il modulo, genera un PDF, lo firma con la propria firma
digitale e lo ricarica dal portale tramite un **token**. Nessuna email.

## Flusso

1. **Nuova richiesta** → wizard → il server genera il PDF (con token stampato sopra)
2. L'utente scarica il PDF e lo firma digitalmente (firma esterna propria)
3. **Invia documentazione** → inserisce il token → carica PDF firmato + allegati
4. Il sistema notifica FORMEDIL; l'admin gestisce le richieste dal pannello

Stati richiesta: `GENERATA → FIRMATA_CARICATA → IN_VERIFICA → APPROVATA / RESPINTA`

## Struttura del repository

```
shared/
  form-schema.json        Schema canonico dei campi (UNICA fonte di verità)
backend/
  formedil-moduli/        Plugin WordPress headless (PSR-4, namespace Formedil\Moduli)
    formedil-moduli.php   Bootstrap
    src/                  Core, Rest, Schema
    schema/               Copia dello schema per il plugin (sync da /shared)
frontend/
  src/
    config.js             Configurazione centralizzata (API base, ecc.)
    api/client.js         Client API centralizzato
    components/           Componenti riutilizzabili
    pages/                Home (due porte), Nuova richiesta, Invia documentazione
    styles/               variables.css (custom properties) + global.css
scripts/
  sync-schema.sh          Copia lo schema canonico nel plugin backend
```

## Schema condiviso

`shared/form-schema.json` è l'unica fonte di verità per campi, varianti,
validazioni e step. Il **frontend** lo importa via alias `@shared`; il
**backend** ne usa una copia in `backend/formedil-moduli/schema/`.
Dopo ogni modifica allo schema:

```bash
bash scripts/sync-schema.sh
```

## Backend (WordPress headless)

Plugin in `backend/formedil-moduli/`. Namespace REST: `/wp-json/formedil/v1/`.

Endpoint:
- `GET /health` — stato del servizio
- `GET /schema?variante=DTL|ENTE` — schema dei campi
- `POST /richieste` — crea richiesta, genera PDF + token. Body: `{ "variante": "DTL|ENTE", "dati": { ... } }`. Risposta: `{ token, pdf_url, invio_url }`
- `GET /richieste/{token}` — riepilogo minimo (per la pagina di invio)
- `GET /richieste/{token}/pdf` — download del PDF generato

Installazione:
```bash
cd backend/formedil-moduli
composer install      # richiesto: installa mPDF + mpdf/qrcode per la generazione PDF
```
Poi copiare/symlinkare la cartella `formedil-moduli` in `wp-content/plugins/` e
attivarla (l'attivazione crea la tabella `wp_formedil_richieste` e la cartella
PDF protetta in `uploads/formedil/`). PHP ≥ 8.0.

La base URL del frontend (per i link/QR di invio) è configurabile col filtro
`formedil_frontend_base_url`.

## Frontend (React + Vite)

```bash
cd frontend
npm install
npm run dev      # sviluppo
npm run build    # build di produzione
```

Variabile d'ambiente: `VITE_API_BASE` (default
`https://moduli.formedillecce.it/wp-json/formedil/v1`).

## Roadmap sprint

- **S0** ✅ Schema dati, scaffold backend/frontend, namespace REST, homepage a due porte
- **S1** ✅ Backend richieste: modello dati, `POST /richieste`, generazione PDF server-side (mPDF + QR), token, validazione schema-driven (CF/P.IVA)
- **S2** ✅ Wizard frontend multi-step schema-driven, validazioni inline (CF/P.IVA), autosave bozza, incolla partecipanti da Excel, pagina esito (token + download PDF)
- **S3** Firma & re-upload: pagina token, upload PDF firmato + allegati, notifica FORMEDIL
- **S4** Pannello admin (elenco, stati, download) — palette blue/grey, JWT
- **S8** ✅ Hardening: rate limit endpoint pubblici, cap n°/dimensione allegati, audit log (cronologia in admin)
- _(rinviato)_ Retention GDPR: cancellazione automatica oltre soglia — da definire con la base giuridica di conservazione

## Hardening (S8)

**Rate limiting** sugli endpoint pubblici, per IP, basato su transient WP:
`POST /richieste` (10 / 10 min), `POST /richieste/{token}/invio` (20 / 10 min),
`GET /richieste/{token}` (30 / 5 min, anti-enumerazione token). Oltre soglia →
`429` con header `Retry-After`. Limiti sovrascrivibili:

```php
add_filter('formedil_rate_limit', function ($conf, $bucket) {
    if ($bucket === 'crea') { $conf['max'] = 5; }   // 0 = disattiva
    return $conf;
}, 10, 2);
// Dietro reverse proxy, correggere l'IP del client:
add_filter('formedil_client_ip', fn() => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
```

**Cap allegati** per invio: max `AllegatoStorage::MAX_ALLEGATI` (10) file liberi
oltre al PDF firmato e max `MAX_TOTAL_SIZE` (40 MB) totali. Validato prima di
scrivere su disco.

**Audit log** (tabella `wp_formedil_audit`): registra `RICHIESTA_CREATA`,
`INVIO_RICEVUTO`, `STATO_CAMBIATO` con autore (utente admin o "Richiedente") e
IP. Visibile nella sezione **Cronologia** del dettaglio richiesta.

> ⚠️ Su installazioni già attive, la nuova tabella `wp_formedil_audit` viene
> creata riattivando il plugin (disattiva → attiva), via `dbDelta` idempotente.

Test standalone (senza WP/DB): `php backend/test/test-hardening.php`.
