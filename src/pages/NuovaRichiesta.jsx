import { useParams, useNavigate } from 'react-router-dom';
import schema from '@shared/form-schema.json';
import './NuovaRichiesta.css';

/**
 * Scelta della variante (DTL / ENTE).
 * Il wizard di compilazione vero e proprio arriva in S2; qui prepariamo
 * la selezione e mostriamo cosa serve per ciascuna variante.
 */
export default function NuovaRichiesta() {
  const { variante } = useParams();
  const navigate = useNavigate();

  // Step 1: scelta variante.
  if (!variante) {
    return (
      <section className="nuova">
        <h1>Nuova richiesta</h1>
        <p className="nuova__lead">Chi presenta la richiesta?</p>

        <div className="nuova__variants">
          {Object.entries(schema.variants).map(([key, v]) => (
            <button
              key={key}
              type="button"
              className="variant-card"
              onClick={() => navigate(`/nuova/${key}`)}
            >
              <span className="variant-card__tag">{key}</span>
              <h2>{v.label}</h2>
              <p>{v.subtitle}</p>
              <ul className="variant-card__allegati">
                {v.allegati.map((a) => (
                  <li key={a}>{a}</li>
                ))}
              </ul>
            </button>
          ))}
        </div>
      </section>
    );
  }

  // Step 2: variante scelta -> placeholder del wizard (S2).
  const v = schema.variants[variante];
  if (!v) {
    return (
      <section className="nuova">
        <h1>Variante non valida</h1>
        <button className="btn btn--ghost" onClick={() => navigate('/nuova')}>
          ← Torna alla scelta
        </button>
      </section>
    );
  }

  const steps = schema.steps.filter(
    (s) => !s.variants || s.variants.includes(variante)
  );

  return (
    <section className="nuova">
      <h1>
        Nuova richiesta · <span className="nuova__variant">{v.label}</span>
      </h1>
      <p className="nuova__lead">
        Il modulo si compone di {steps.length} passaggi. Il wizard sarà attivo
        nello sprint S2.
      </p>

      <ol className="nuova__steps">
        {steps.map((s) => (
          <li key={s.id}>
            <strong>{s.title}</strong>
            {s.description ? <span> — {s.description}</span> : null}
          </li>
        ))}
      </ol>

      <button className="btn btn--ghost" onClick={() => navigate('/nuova')}>
        ← Cambia variante
      </button>
    </section>
  );
}
