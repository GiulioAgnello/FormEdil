import { useState } from 'react';
import FileDropField from './FileDropField.jsx';
import FileListField from './FileListField.jsx';
import { api } from '@/api/client';

const MAX_SIZE = 10 * 1024 * 1024; // 10 MB, allineato al backend
const MIME_FIRMATO = ['application/pdf'];
const MIME_ALLEGATO = ['application/pdf', 'image/jpeg', 'image/png'];

/** Validazione lato client (specchio del backend) per feedback immediato. */
function checkFile(file, mimes) {
  if (!file) return '';
  if (file.size > MAX_SIZE) return 'File troppo grande (massimo 10 MB).';
  if (file.type && !mimes.includes(file.type)) return 'Tipo di file non ammesso.';
  return '';
}

/**
 * Area di caricamento: PDF firmato (obbligatorio) + allegati liberi (opzionali).
 * onDone() viene chiamato quando l'invio va a buon fine.
 */
export default function UploadInvio({ token, onDone }) {
  const [firmato, setFirmato] = useState(null);
  const [allegati, setAllegati] = useState([]);
  const [errors, setErrors] = useState({});
  const [submitError, setSubmitError] = useState('');
  const [busy, setBusy] = useState(false);

  function validate() {
    const e = {};
    if (!firmato) e.firmato = 'Carica il PDF firmato.';
    else {
      const ef = checkFile(firmato, MIME_FIRMATO);
      if (ef) e.firmato = ef;
    }
    const badAllegato = allegati.find((f) => checkFile(f, MIME_ALLEGATO));
    if (badAllegato) e.allegati = 'Un allegato non è valido (PDF/JPG/PNG, max 10 MB).';
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  async function handleSubmit(ev) {
    ev.preventDefault();
    setSubmitError('');
    if (!validate()) return;

    setBusy(true);
    try {
      const res = await api.inviaDocumentazione(token, { firmato, allegati });
      onDone(res);
    } catch (err) {
      // 422: errori per campo dal backend.
      const fieldErrors = err.payload?.errors;
      if (fieldErrors && typeof fieldErrors === 'object') {
        setErrors((prev) => ({ ...prev, ...mapBackendErrors(fieldErrors) }));
      }
      setSubmitError(err.message || 'Invio non riuscito. Riprova.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="card upload" onSubmit={handleSubmit}>
      <FileDropField
        label="Modulo firmato (PDF)"
        hint="Il PDF generato, firmato con la tua firma digitale. Solo PDF, max 10 MB."
        accept="application/pdf"
        file={firmato}
        onSelect={setFirmato}
        error={errors.firmato}
        required
      />

      <FileListField
        label="Altri allegati (opzionali)"
        hint="Es. documento d'identità, mandato. PDF, JPG o PNG, max 10 MB ciascuno."
        accept="application/pdf,image/jpeg,image/png"
        files={allegati}
        onChange={setAllegati}
        error={errors.allegati}
      />

      {submitError ? <p className="upload__error">{submitError}</p> : null}

      <button type="submit" className="btn btn--primary" disabled={busy || !firmato}>
        {busy ? 'Invio in corso…' : 'Invia documentazione'}
      </button>
    </form>
  );
}

/** Mappa le chiavi d'errore del backend (firmato, allegati_0…) sui campi UI. */
function mapBackendErrors(be) {
  const out = {};
  if (be.firmato) out.firmato = be.firmato;
  const allegatoKey = Object.keys(be).find((k) => k.startsWith('allegati'));
  if (allegatoKey) out.allegati = be[allegatoKey];
  return out;
}
