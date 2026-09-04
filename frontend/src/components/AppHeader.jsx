import { NavLink } from "react-router-dom";
import "./AppHeader.css";

/** Contatti pubblici FORMEDIL Lecce, mostrati nella barra superiore. */
const CONTATTI = {
  telefono: "+39 0832 332095",
  telefonoHref: "tel:+390832332095",
  email: "info@formedillecce.it",
  emailHref: "mailto:info@formedillecce.it",
};

/** Header riutilizzabile lato utente: barra contatti + brand + nav interna. */
export default function AppHeader() {
  return (
    <header className="app-header">
      <div className="app-header__topbar">
        <div className="app-header__topbar-inner">
          <a className="app-header__contact" href={CONTATTI.telefonoHref}>
            <svg viewBox="0 0 24 24" aria-hidden="true" className="app-header__icon">
              <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1L6.6 10.8z" />
            </svg>
            {CONTATTI.telefono}
          </a>
          <a className="app-header__contact" href={CONTATTI.emailHref}>
            <svg viewBox="0 0 24 24" aria-hidden="true" className="app-header__icon">
              <path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1.7 2 7.3 5.6L19.3 7H4.7zM4 8.4V18h16V8.4l-8 6.1-8-6.1z" />
            </svg>
            {CONTATTI.email}
          </a>
        </div>
      </div>

      <div className="app-header__inner">
        <NavLink
          to="/"
          className="app-header__brand"
          aria-label="FORMEDIL Lecce — Home"
        >
          <img
            className="app-header__logo-img"
            src="/logo-formedil.png"
            alt="FORMEDIL Lecce — Ente Unico Formazione e Sicurezza"
          />
        </NavLink>

        <nav className="app-header__nav" aria-label="Navigazione principale">
          <NavLink
            to="/"
            end
            className={({ isActive }) =>
              "app-header__nav-link" + (isActive ? " app-header__nav-link--active" : "")
            }
          >
            Home
          </NavLink>
          <NavLink
            to="/invio"
            className={({ isActive }) =>
              "app-header__nav-link" + (isActive ? " app-header__nav-link--active" : "")
            }
          >
            Invia documentazione
          </NavLink>
        </nav>
      </div>
    </header>
  );
}
