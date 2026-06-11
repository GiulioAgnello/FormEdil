import { Link } from 'react-router-dom';
import './AppHeader.css';

/** Header riutilizzabile lato utente. */
export default function AppHeader() {
  return (
    <header className="app-header">
      <div className="app-header__inner">
        <Link to="/" className="app-header__brand">
          <span className="app-header__logo">FORMEDIL</span>
          <span className="app-header__sub">Lecce · Moduli online</span>
        </Link>
      </div>
    </header>
  );
}
