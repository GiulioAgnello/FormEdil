import { Link } from "react-router-dom";
import "./AppFooter.css";

/** Footer riutilizzabile lato utente: solo contenuti/link della SPA. */
export default function AppFooter() {
  const anno = new Date().getFullYear();

  return (
    <footer className="app-footer">
      <div className="app-footer__inner">
        <div className="app-footer__col">
          <img
            className="app-footer__logo"
            src="/logo-formedil-white.png"
            alt="FORMEDIL Lecce — Ente Unico Formazione e Sicurezza"
          />
          <p className="app-footer__text">
            Ente Unico Formazione e Sicurezza in Edilizia — Via Belgio, 73100
            Lecce.
          </p>
        </div>

        <div className="app-footer__col">
          <span className="app-footer__heading">Contatti</span>
          <a className="app-footer__link" href="tel:+390832332095">
            +39 0832 332095
          </a>
          <a className="app-footer__link" href="mailto:info@formedillecce.it">
            info@formedillecce.it
          </a>
        </div>

        <div className="app-footer__col">
          <span className="app-footer__heading">Modulo di richiesta</span>
          <Link className="app-footer__link" to="/">
            Home
          </Link>
          <Link className="app-footer__link" to="/invio">
            Invia documentazione firmata
          </Link>
        </div>
      </div>

      <div className="app-footer__bottom">
        © {anno} FORMEDIL Lecce — Ente Unico Formazione e Sicurezza - powered by{" "}
        <a
          className="app-footer__bottom-link"
          href="https://www.media-aptitude.it/"
        >
          Media-Aptitude
        </a>
      </div>
    </footer>
  );
}
