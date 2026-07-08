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
 */
export default function NuovaRichiesta() {
  const { variante } = useParams();
  const navigate = useNavigate();

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
        <h1>
          Nuova richiesta · <span className="nuova__variant">{v.label}</span>
        </h1>
        <button
          className="btn btn--ghost btn--sm"
          onClick={() => navigate("/")}
        >
          Cambia variante
        </button>
      </div>
      <Wizard schema={schema} variante={variante} onSubmit={handleSubmit} />
    </section>
  );
}
