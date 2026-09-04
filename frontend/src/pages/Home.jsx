import { useNavigate } from "react-router-dom";
import schema from "@shared/form-schema.json";
import "./Home.css";

/** Punti informativi rapidi mostrati sotto l'intro (riempimento visivo a card). */
const INFO_CARDS = [
  {
    titolo: "Prima di iniziare",
    testo:
      "Tieni a portata di mano i dati dell'azienda o dell'ente, il programma del corso e l'elenco dei partecipanti.",
  },
  {
    titolo: "Tempistiche",
    testo:
      "Trasmetti la richiesta almeno 15 giorni prima dell'erogazione del corso, come previsto dal regolamento.",
  },
  {
    titolo: "Assistenza",
    testo:
      "Per qualsiasi dubbio sulla compilazione contatta FORMEDIL Lecce ai recapiti in fondo alla pagina.",
  },
];

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
      <span className="badge badge--primary home__eyebrow">Art. 37 D.Lgs 81/2008</span>
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

      <div className="home__info">
        {INFO_CARDS.map((c) => (
          <div key={c.titolo} className="card home__info-card">
            <h3 className="home__info-card-title">{c.titolo}</h3>
            <p className="home__info-card-text">{c.testo}</p>
          </div>
        ))}
      </div>

      <div className="card home__secondary">
        <h2>Hai già compilato il modulo e lo hai firmato?</h2>
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
