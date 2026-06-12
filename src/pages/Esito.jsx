import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { api } from '@/api/client';
import './Esito.css';

/**
 * Pagina di esito dopo la generazione: token ben visibile, download PDF,
 * e indicazioni per la fase di invio.
 */
export default function Esito() {
  const { token } = useParams();
  const navigate = useNavigate();
  const [riepilogo, setRiepilogo] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let alive = true;
    api
      .getRichiestaByToken(token)
      .then((r) => alive && setRiepilogo(r))
      .catch((e) => alive && setError(e.message || 'Richiesta non trovata.'));
    return () => {
      alive = false;
    };
  }, [token]);

  return (
    <section className="esito">
      <div className="esito__icon" aria-hidden="true">✓</div>
      <h1>Richiesta generata</h1>
      <p className="esito__lead">
        Il modulo è stato compilato e salvato. Scarica il PDF, firmalo
        digitalmente e ricaricalo dal portale usando il codice qui sotto.
      </p>

      <div className="card esito__token">
        <span className="esito__tokenLabel">Codice di invio</span>
        <span className="esito__tokenCode">{token}</span>
        <span className="esito__tokenHint">
          Conservalo: ti serve per caricare il modulo firmato. È stampato anche sul PDF.
        </span>
      </div>

      {error ? <p className="esito__error">{error}</p> : null}
      {riepilogo ? (
        <p className="esito__recap">
          {riepilogo.variante} · {riepilogo.denominazione || '—'} · stato:{' '}
          <strong>{riepilogo.stato}</strong>
        </p>
      ) : null}

      <div className="esito__actions">
        <a className="btn btn--primary" href={api.pdfUrl(token)} target="_blank" rel="noreferrer">
          ↓ Scarica il PDF
        </a>
        <button className="btn btn--ghost" onClick={() => navigate(`/invio/${token}`)}>
          Vai a "Invia documentazione" →
        </button>
      </div>

      <p className="esito__home">
        <Link to="/">Torna alla home</Link>
      </p>
    </section>
  );
}
