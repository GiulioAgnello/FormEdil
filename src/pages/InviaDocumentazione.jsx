import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import './InviaDocumentazione.css';

/**
 * Seconda porta del portale: l'utente inserisce il token ricevuto col PDF
 * per abilitare il caricamento dei documenti firmati.
 * La validazione del token e l'upload reale arrivano in S3; qui prepariamo
 * il form di accesso.
 */
export default function InviaDocumentazione() {
  const { token: tokenFromUrl } = useParams();
  const navigate = useNavigate();
  const [token, setToken] = useState(tokenFromUrl || '');

  function handleSubmit(e) {
    e.preventDefault();
    const value = token.trim();
    if (!value) return;
    // S3: qui validiamo il token via API e mostriamo l'area di upload.
    navigate(`/invio/${encodeURIComponent(value)}`);
  }

  return (
    <section className="invio">
      <h1>Invia documentazione</h1>
      <p className="invio__lead">
        Inserisci il <strong>token</strong> che trovi stampato sul PDF generato.
        Ti darà accesso al caricamento del modulo firmato e degli allegati.
      </p>

      <form className="card invio__form" onSubmit={handleSubmit}>
        <label htmlFor="token" className="invio__label">
          Token della richiesta
        </label>
        <input
          id="token"
          type="text"
          className="invio__input"
          placeholder="es. FME-XXXX-XXXX-XXXX"
          value={token}
          onChange={(e) => setToken(e.target.value)}
          autoComplete="off"
          spellCheck={false}
        />
        <button type="submit" className="btn btn--primary" disabled={!token.trim()}>
          Continua
        </button>
      </form>

      {tokenFromUrl ? (
        <p className="invio__note">
          Token rilevato dal link: <code>{tokenFromUrl}</code>. La verifica e
          l'area di upload saranno attive nello sprint S3.
        </p>
      ) : null}
    </section>
  );
}
