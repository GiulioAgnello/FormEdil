import { useNavigate } from "react-router-dom";
import schema from "@shared/form-schema.json";
import "./Home.css";

/**
 * Homepage unificata.
 * L'utente si identifica subito sul tipo di richiesta (variante DTL/ENTE)
 * e va dritto al wizard (/nuova/:variante). L'invio della documentazione
 * firmata è un'azione secondaria in fondo.
 */
export default function Home() {
  const navigate = useNavigate();

  return (
    <section className="home">
      <div className="home__intro">
        <h1>Richiesta di collaborazione FORMEDIL Lecce</h1>
        <p>
          Art. 37 comma 12 D.Lgs 81/2008 · Accordo Stato Regioni del 17/04/2025.
        </p>
      </div>
      <p className="home__intro--highlight">
        Invia il modulo online, scarica il PDF, firmalo digitalmente e
        ricaricalo.
      </p>

      <h2 className="home__prompt">Chi presenta la richiesta?</h2>
      <div className="home__variants">
        {Object.entries(schema.variants).map(([key, v]) => (
          <button
            key={key}
            type="button"
            className="variant-card"
            onClick={() => navigate(`/nuova/${key}`)}
          >
            <span className="variant-card__tag">{key}</span>
            <h3>{v.label}</h3>
            <p>{v.subtitle}</p>
            <span className="variant-card__cta">Inizia la compilazione →</span>
          </button>
        ))}
      </div>

      <div className="home__secondary">
        <p>Hai già compilato il modulo e lo hai firmato digitalmente?</p>
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() => navigate("/invio")}
        >
          ↑ Invia la documentazione firmata
        </button>
      </div>
    </section>
  );
}
