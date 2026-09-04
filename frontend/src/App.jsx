import { Routes, Route } from 'react-router-dom';
import AppHeader from './components/AppHeader.jsx';
import AppFooter from './components/AppFooter.jsx';
import Home from './pages/Home.jsx';
import NuovaRichiesta from './pages/NuovaRichiesta.jsx';
import Esito from './pages/Esito.jsx';
import InviaDocumentazione from './pages/InviaDocumentazione.jsx';

/**
 * Routing principale della SPA (solo lato utente).
 * La gestione amministrativa vive in wp-admin, non nella SPA.
 */
export default function App() {
  return (
    <div className="app-shell">
      <AppHeader />
      <main className="container app-shell__main">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/nuova" element={<NuovaRichiesta />} />
          <Route path="/nuova/:variante" element={<NuovaRichiesta />} />
          <Route path="/esito/:token" element={<Esito />} />
          <Route path="/invio" element={<InviaDocumentazione />} />
          <Route path="/invio/:token" element={<InviaDocumentazione />} />
          <Route path="*" element={<Home />} />
        </Routes>
      </main>
      <AppFooter />
    </div>
  );
}
