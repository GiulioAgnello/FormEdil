# Test manuale del backend (S1)

Prerequisiti: WordPress locale attivo con il plugin **FORMEDIL Moduli** installato
(`composer install` eseguito) e attivato. Sostituisci `formedil.local` con l'host
del tuo sito.

## 1. Health check

```
GET http://formedil.local/wp-json/formedil/v1/health
```
Atteso: `{ "ok": true, "service": "formedil-moduli", ... }`

## 2. Schema dei campi

```
GET http://formedil.local/wp-json/formedil/v1/schema
```

## 3. Crea una richiesta (genera PDF + token)

PowerShell (Windows):
```powershell
curl.exe -X POST http://formedil.local/wp-json/formedil/v1/richieste `
  -H "Content-Type: application/json" `
  --data "@richiesta-dtl.example.json"
```

Bash:
```bash
curl -X POST http://formedil.local/wp-json/formedil/v1/richieste \
  -H "Content-Type: application/json" \
  --data @richiesta-dtl.example.json
```

Risposta attesa (201):
```json
{ "ok": true, "token": "FME-XXXX-XXXX-XXXX",
  "pdf_url": "http://.../richieste/FME-XXXX-XXXX-XXXX/pdf",
  "invio_url": "https://moduli.formedillecce.it/invio/FME-XXXX-XXXX-XXXX" }
```

## 4. Scarica il PDF

Apri il `pdf_url` della risposta nel browser, oppure:
```
GET http://formedil.local/wp-json/formedil/v1/richieste/FME-XXXX-XXXX-XXXX/pdf
```

## 5. Riepilogo per token (pagina di invio)

```
GET http://formedil.local/wp-json/formedil/v1/richieste/FME-XXXX-XXXX-XXXX
```

## Test di validazione (errori attesi)

Modifica `richiesta-dtl.example.json` introducendo un errore (es. `azienda_piva`
a `00743110158`, oppure togli `modalita`) e rifai la POST: deve rispondere `422`
con l'elenco degli errori per campo.

> Nota: per la variante ENTE prepara un payload con i campi `ente_*`,
> `mandato_datore`, `impresa_*` e `rpf_*` (vedi `shared/form-schema.json`).

## Anteprima grafica del PDF (senza WordPress)

Per ritoccare l'aspetto del PDF velocemente, genera un'anteprima HTML che usa
lo **stesso template e lo stesso CSS** del PDF reale:

```bash
cd backend/test
php render-preview.php          # variante DTL
php render-preview.php ENTE     # variante ENTE
```

Apri `preview.html` nel browser. Modifica
`backend/formedil-moduli/templates/pdf-richiesta.css`, rilancia il comando e
ricarica la pagina. Il QR è un segnaposto (è una funzione di mPDF), ma
layout, colori e tipografia sono fedeli al PDF.

Il logo va in `backend/formedil-moduli/templates/assets/logo.jpg` (o `.png`).
