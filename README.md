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

Endpoint S0:
- `GET /health` — stato del servizio
- `GET /schema?variante=DTL|ENTE` — schema dei campi

Installazione: copiare la cartella `formedil-moduli` in `wp-content/plugins/`
e attivarla. PHP ≥ 8.0. Autoloading PSR-4 (Composer opzionale, è incluso un
loader minimale).

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
- **S1** Backend richieste: modello dati, `POST /richieste`, generazione PDF server-side, token
- **S2** Wizard frontend multi-step, validazioni (CF/P.IVA), autosave, incolla partecipanti
- **S3** Firma & re-upload: pagina token, upload PDF firmato + allegati, notifica FORMEDIL
- **S4** Pannello admin (elenco, stati, download) — palette blue/grey, JWT
- **S5** Hardening: validazione file, rate limit, retention GDPR, sicurezza
