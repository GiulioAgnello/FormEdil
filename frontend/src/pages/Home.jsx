import { useNavigate } from "react-router-dom";
import schema from "@shared/form-schema.json";
import "./Home.css";

/**
 * Homepage unificata.
 * L'utente si identifica subito sul tipo di richiesta (variante IMPRESA/ENTE)
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
        Invia il modulo online, scarica il PDF, firmalo e ricaricalo.
      </p>

      <h1 className="home__prompt">Chi presenta la richiesta?</h1>
      <div className="home__variants">
        {Object.entries(schema.variants).map(([key, v]) => (
          <div key={key} className="variant-choice">
            <h3>{v.label}</h3>
            <picture className="variant-choice__picture">
              <source
                srcSet={key === "IMPRESA" ? "/impresa.webp" : "/ente.webp"}
                type="image/webp"
              />
              <img
                className="variant-choice__photo"
                src={key === "IMPRESA" ? "/impresa.jpg" : "/ente.jpg"}
                alt={v.label}
                width="800"
                height="436"
                loading="lazy"
                decoding="async"
              />
            </picture>
            <p className="variant-choice__subtitle">{v.subtitle}</p>
            <button
              type="button"
              className="variant-choice__btn"
              onClick={() => navigate(`/nuova/${key}`)}
            >
              Inizia la compilazione
            </button>
          </div>
        ))}
      </div>

      <div className="home__secondary">
        <h2>Hai già compilato il modulo e lo hai firmato ?</h2>
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() => navigate("/invio")}
        >
          Invia la documentazione firmata
        </button>
      </div>
    </section>
  );
}
