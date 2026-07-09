import { useState } from "react";
import { useParams, useNavigate, Navigate } from "react-router-dom";
import schema from "@shared/form-schema.json";
import Wizard from "@/components/wizard/Wizard";
import { api } from "@/api/client";
import { clearBozza } from "@/utils/storage";
import "@/components/wizard/wizard.css";
import "./NuovaRichiesta.css";

/**
 * Compilazione della richiesta tramite wizard.
 * La scelta della variante avviene in Home; qui arriva già /nuova/:variante.
 * In alto si può passare direttamente all'altra variante (con conferma).
 */
export default function NuovaRichiesta() {
  const { variante } = useParams();
  const navigate = useNavigate();
  const [confermaCambio, setConfermaCambio] = useState(false);

  // Senza variante non c'è nulla da compilare: torna alla home.
  if (!variante) {
    return <Navigate to="/" replace />;
  }

  const v = schema.variants[variante];
  if (!v) {
    return (
      <section className="nuova">
        <h1>Variante non valida</h1>
        <button className="btn btn--ghost" onClick={() => navigate("/")}>
          ← Torna alla home
        </button>
      </section>
    );
  }

  // Variante opposta: generico, funziona anche se un giorno se ne aggiungono altre.
  const altraChiave = Object.keys(schema.variants).find((k) => k !== variante);
  const altra = altraChiave ? schema.variants[altraChiave] : null;

  // Invio: crea la richiesta e va alla pagina esito; rilancia gli errori al wizard.
  const handleSubmit = async (dati) => {
    try {
      const res = await api.creaRichiesta(variante, dati);
      clearBozza(variante);
      navigate(`/esito/${res.token}`);
    } catch (err) {
      throw { errors: err.payload?.errors || null, message: err.message };
    }
  };

  return (
    <section className="nuova">
      <div className="nuova__bar">
        <button
          className="btn btn--ghost btn--sm nuova__back"
          onClick={() => navigate("/")}
        >
          ← Torna alla home
        </button>
      </div>
      <div className="nuova__bar-left">
        <h1>
          Nuova richiesta · <span className="nuova__variant">{v.label}</span>
        </h1>
        {altra && (
          <button
            className="btn btn--ghost btn--sm nuova__switch"
            onClick={() => setConfermaCambio(true)}
          >
            Passa a: <strong>{altra.label}</strong>
          </button>
        )}
      </div>

      {/* key={variante}: al cambio variante il wizard si rimonta pulito
          e carica la bozza corretta (le bozze sono separate per variante). */}
      <Wizard
        key={variante}
        schema={schema}
        variante={variante}
        onSubmit={handleSubmit}
      />

      {confermaCambio && altra && (
        <div
          className="nuova__confirm-overlay"
          role="dialog"
          aria-modal="true"
          aria-labelledby="confirm-title"
          onClick={() => setConfermaCambio(false)}
        >
          <div className="nuova__confirm" onClick={(e) => e.stopPropagation()}>
            <h2 id="confirm-title" className="nuova__confirm-title">
              Cambiare modulo?
            </h2>
            <p className="nuova__confirm-text">
              Stai per passare al modulo <strong>{altra.label}</strong>. I dati
              già inseriti in <strong>{v.label}</strong> restano salvati e li
              ritrovi se torni indietro.
            </p>
            <div className="nuova__confirm-actions">
              <button
                className="btn btn--ghost"
                onClick={() => setConfermaCambio(false)}
              >
                Annulla
              </button>
              <button
                className="btn btn--primary"
                onClick={() => {
                  setConfermaCambio(false);
                  navigate(`/nuova/${altraChiave}`);
                }}
              >
                Passa a {altra.label}
              </button>
            </div>
          </div>
        </div>
      )}
    </section>
  );
}
