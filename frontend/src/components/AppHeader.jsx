import { Link } from "react-router-dom";
import "./AppHeader.css";

/** Header riutilizzabile lato utente. */
export default function AppHeader() {
  return (
    <header className="app-header">
      <div className="app-header__inner">
        <Link
          to="/"
          className="app-header__brand"
          aria-label="FORMEDIL Lecce — Home"
        >
          <img
            className="app-header__logo-img"
            src="/logo-formedil.jpg"
            alt="FORMEDIL Lecce — Ente Unico Formazione e Sicurezza"
          />
        </Link>
      </div>
    </header>
  );
}
