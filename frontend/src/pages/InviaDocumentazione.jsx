import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { api } from '@/api/client';
import UploadInvio from '@/components/upload/UploadInvio.jsx';
import './InviaDocumentazione.css';

/**
 * Seconda porta del portale.
 * - Senza token: form per inserire il codice ricevuto col PDF.
 * - Con token (/invio/:token): valida il codice e, se la richiesta è ancora
 *   in attesa, mostra l'area di upload del modulo firmato + allegati (S3).
 */
export default function InviaDocumentazione() {
  const { token: tokenFromUrl } = useParams();

  if (!tokenFromUrl) return <TokenForm />;
  return <InvioToken token={tokenFromUrl} />;
}

/** Inserimento manuale del token. */
function TokenForm() {
  const navigate = useNavigate();
  const [token, setToken] = useState('');

  function handleSubmit(e) {
    e.preventDefault();
    const value = token.trim();
    if (!value) return;
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
    </section>
  );
}

/** Fase di upload per un token specifico. */
function InvioToken({ token }) {
  const [riepilogo, setRiepilogo] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [inviato, setInviato] = useState(null);

  useEffect(() => {
    let alive = true;
    setLoading(true);
    api
      .getRichiestaByToken(token)
      .then((r) => alive && setRiepilogo(r))
      .catch((e) => alive && setError(e.message || 'Codice non valido o richiesta inesistente.'))
      .finally(() => alive && setLoading(false));
    return () => {
      alive = false;
    };
  }, [token]);

  if (loading) {
    return (
      <section className="invio">
        <h1>Invia documentazione</h1>
        <p className="invio__lead">Verifica del codice in corso…</p>
      </section>
    );
  }

  if (error) {
    return (
      <section className="invio">
        <h1>Codice non valido</h1>
        <p className="invio__error">{error}</p>
        <p className="invio__note">
          <Link to="/invio">Inserisci un altro codice</Link>
        </p>
      </section>
    );
  }

  // Invio appena completato.
  if (inviato) {
    return (
      <section className="invio invio--ok">
        <div className="invio__icon" aria-hidden="true">✓</div>
        <h1>Documenti ricevuti</h1>
        <p className="invio__lead">
          Abbiamo ricevuto il modulo firmato{inviato.allegati > 1 ? ' e gli allegati' : ''}.
          FORMEDIL Lecce procederà con la verifica.
        </p>
        <p className="invio__note">
          <Link to="/">Torna alla home</Link>
        </p>
      </section>
    );
  }

  const giaInviato = riepilogo?.stato && riepilogo.stato !== 'GENERATA';
  if (giaInviato) {
    return (
      <section className="invio">
        <h1>Documenti già inviati</h1>
        <p className="invio__lead">
          Per questa richiesta i documenti risultano già caricati (stato:{' '}
          <strong>{riepilogo.stato}</strong>). Non è necessario inviarli di nuovo.
        </p>
        <p className="invio__note">
          <Link to="/">Torna alla home</Link>
        </p>
      </section>
    );
  }

  return (
    <section className="invio">
      <h1>Invia documentazione</h1>
      <p className="invio__lead">
        Richiesta <strong>{riepilogo?.variante}</strong>
        {riepilogo?.denominazione ? ` · ${riepilogo.denominazione}` : ''} · codice{' '}
        <code>{token}</code>.
      </p>
      <UploadInvio token={token} onDone={setInviato} />
    </section>
  );
}
