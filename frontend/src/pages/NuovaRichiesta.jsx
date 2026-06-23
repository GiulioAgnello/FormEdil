import { useParams, useNavigate } from "react-router-dom";
import schema from "@shared/form-schema.json";
import Wizard from "@/components/wizard/Wizard";
import { api } from "@/api/client";
import { clearBozza } from "@/utils/storage";
import "@/components/wizard/wizard.css";
import "./NuovaRichiesta.css";

/**
 * Scelta variante (DTL/ENTE) e compilazione tramite wizard.
 */
export default function NuovaRichiesta() {
  const { variante } = useParams();
  const navigate = useNavigate();

  // Step 1: scelta variante.
  if (!variante) {
    return (
      <section className="nuova">
        <h1>Nuova richiesta</h1>
        <p className="nuova__lead">Chi presenta la richiesta?</p>
        <div className="nuova__variants">
          {Object.entries(schema.variants).map(([key, v]) => (
            <button
              key={key}
              type="button"
              className="variant-card"
              onClick={() => navigate(`/nuova/${key}`)}
            >
              <span className="variant-card__tag">{key}</span>
              <h2>{v.label}</h2>
              <p>{v.subtitle}</p>
              <ul className="variant-card__allegati">
                {v.allegati.map((a) => (
                  <li key={a}>{a}</li>
                ))}
              </ul>
            </button>
          ))}
        </div>
      </section>
    );
  }

  const v = schema.variants[variante];
  if (!v) {
    return (
      <section className="nuova">
        <h1>Variante non valida</h1>
        <button className="btn btn--ghost" onClick={() => navigate("/nuova")}>
          ← Torna alla scelta
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
          onClick={() => navigate("/nuova")}
        >
          Cambia variante
        </button>
      </div>
      <Wizard schema={schema} variante={variante} onSubmit={handleSubmit} />
    </section>
  );
}
