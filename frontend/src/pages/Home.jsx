import { useNavigate } from "react-router-dom";
import "./Home.css";

/**
 * Homepage a due porte:
 *  1) Nuova richiesta  -> compilazione del modulo (wizard, S2)
 *  2) Invia documentazione -> inserimento token + upload (S3)
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

      <div className="home__doors">
        <button
          type="button"
          className="door"
          onClick={() => navigate("/nuova")}
        >
          <span className="door__icon" aria-hidden="true">
            ＋
          </span>
          <h2>Nuova richiesta</h2>
          <p>Compila il modulo e genera il PDF da firmare.</p>
          <span className="door__cta">Inizia la compilazione →</span>
        </button>

        <button
          type="button"
          className="door"
          onClick={() => navigate("/invio")}
        >
          <span className="door__icon" aria-hidden="true">
            ↑
          </span>
          <h2>Invia documentazione</h2>
          <p>
            Invia il modulo online firmato insernedo il token indicato sul pdf.
          </p>
          <span className="door__cta">Inserisci il token →</span>
        </button>
      </div>
    </section>
  );
}
