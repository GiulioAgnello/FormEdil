import { useState } from 'react';
import { useWizard } from '@/hooks/useWizard';
import Stepper from './Stepper';
import Field from './Field';

/**
 * Orchestratore del wizard: stepper + campi dello step + navigazione + invio.
 * onSubmit(dati) deve restituire una Promise; in caso di errori di validazione
 * server, rigettare con un oggetto { errors: { campo: messaggio } }.
 */
export default function Wizard({ schema, variante, onSubmit }) {
  const w = useWizard(schema, variante);
  const [maxReached, setMaxReached] = useState(0);
  const [submitting, setSubmitting] = useState(false);
  const [serverMsg, setServerMsg] = useState('');

  const goNext = () => {
    if (w.next()) {
      setMaxReached((m) => Math.max(m, w.stepIndex + 1));
    }
  };

  const findStepOf = (fieldName) => {
    const base = String(fieldName).split('.')[0];
    return w.steps.findIndex((s) => (s.fields || []).some((f) => f.name === base));
  };

  const handleSubmit = async () => {
    if (!w.validateCurrent()) return;
    setSubmitting(true);
    setServerMsg('');
    try {
      await onSubmit(w.dati);
    } catch (err) {
      const fieldErrors = err?.errors || null;
      if (fieldErrors) {
        w.setErrors(fieldErrors);
        const first = Object.keys(fieldErrors)[0];
        const idx = findStepOf(first);
        if (idx >= 0) w.goTo(idx);
        setServerMsg('Controlla i campi evidenziati.');
      } else {
        setServerMsg(err?.message || 'Errore durante l’invio. Riprova.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="wizard">
      <Stepper
        steps={w.steps}
        current={w.stepIndex}
        maxReached={maxReached}
        onGo={w.goTo}
      />

      <div className="wizard__panel card">
        <header className="wizard__head">
          <h2>{w.currentStep?.title}</h2>
          {w.currentStep?.description ? (
            <p className="wizard__desc">{w.currentStep.description}</p>
          ) : null}
        </header>

        <div className="wizard__fields">
          {w.currentFields.map((field) => (
            <div key={field.name} className={`wizard__col wizard__col--${field.col || 12}`}>
              <Field
                schema={schema}
                field={field}
                value={w.dati[field.name]}
                error={w.errors[field.name]}
                onChange={(v) => w.setField(field.name, v)}
              />
            </div>
          ))}
        </div>

        {serverMsg ? <p className="wizard__servermsg">{serverMsg}</p> : null}

        <footer className="wizard__nav">
          <button
            type="button"
            className="btn btn--ghost"
            onClick={w.prev}
            disabled={w.isFirst || submitting}
          >
            ← Indietro
          </button>

          {w.isLast ? (
            <button
              type="button"
              className="btn btn--primary"
              onClick={handleSubmit}
              disabled={submitting}
            >
              {submitting ? 'Generazione…' : 'Invia e genera PDF'}
            </button>
          ) : (
            <button type="button" className="btn btn--primary" onClick={goNext}>
              Avanti →
            </button>
          )}
        </footer>
      </div>
    </div>
  );
}
