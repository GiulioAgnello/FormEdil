import { Routes, Route } from 'react-router-dom';
import AppHeader from './components/AppHeader.jsx';
import Home from './pages/Home.jsx';
import NuovaRichiesta from './pages/NuovaRichiesta.jsx';
import InviaDocumentazione from './pages/InviaDocumentazione.jsx';

/**
 * Routing principale della SPA.
 * S0: struttura a due porte (Nuova richiesta / Invia documentazione).
 * Il wizard e l'upload reali arrivano in S2 e S3.
 */
export default function App() {
  return (
    <>
      <AppHeader />
      <main className="container">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/nuova" element={<NuovaRichiesta />} />
          <Route path="/nuova/:variante" element={<NuovaRichiesta />} />
          <Route path="/invio" element={<InviaDocumentazione />} />
          <Route path="/invio/:token" element={<InviaDocumentazione />} />
          <Route path="*" element={<Home />} />
        </Routes>
      </main>
    </>
  );
}
