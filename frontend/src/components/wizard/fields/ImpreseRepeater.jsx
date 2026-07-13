import Field from '../Field';
import FieldWrapper from './FieldWrapper';
import { fieldActive, emptyValue } from '@/utils/schema';
import { validateRow } from '@/utils/validators';

/**
 * Lista ripetibile di imprese (variante ENTE).
 * Ogni impresa è un mini-form che riusa i componenti campo esistenti:
 * i campi condizionati (casse edili) e il provincia/comune/CAP funzionano
 * valutando la condizione sul singolo oggetto impresa.
 * value = array di oggetti impresa.
 */
export default function ImpreseRepeater({ schema, field, value, error, onChange }) {
  const items = Array.isArray(value) ? value : [];
  const itemFields = field.itemFields || [];
  const max = field.max || 20;

  const emptyItem = () =>
    Object.fromEntries(itemFields.map((f) => [f.name, emptyValue(f)]));

  const add = () => {
    if (items.length >= max) return;
    onChange([...items, emptyItem()]);
  };
  const remove = (i) => onChange(items.filter((_, idx) => idx !== i));
  const setSub = (i, name, v) =>
    onChange(items.map((it, idx) => (idx === i ? { ...it, [name]: v } : it)));

  return (
    <FieldWrapper field={field} error={error}>
      <div className="imprese">
        {items.length === 0 ? (
          <p className="imprese__empty">Nessuna impresa inserita.</p>
        ) : (
          items.map((it, i) => {
            const rowErr = validateRow(
              itemFields.filter((f) => fieldActive(f, 'ENTE', it)),
              it
            );
            return (
              <div key={i} className="imprese__block">
                <div className="imprese__head">
                  <span className="imprese__title">Impresa {i + 1}</span>
                  <button
                    type="button"
                    className="imprese__remove"
                    onClick={() => remove(i)}
                    aria-label="Rimuovi impresa"
                  >
                    ✕ Rimuovi
                  </button>
                </div>
                <div className="wizard__fields">
                  {itemFields
                    .filter((f) => fieldActive(f, 'ENTE', it))
                    .map((f) => (
                      <div
                        key={f.name}
                        className={`wizard__col wizard__col--${f.col || 12}`}
                      >
                        <Field
                          schema={schema}
                          field={f}
                          value={it[f.name]}
                          error={rowErr[f.name]}
                          onChange={(v) => setSub(i, f.name, v)}
                        />
                      </div>
                    ))}
                </div>
              </div>
            );
          })
        )}
        <button
          type="button"
          className="btn btn--ghost btn--sm"
          onClick={add}
          disabled={items.length >= max}
        >
          + Aggiungi impresa
        </button>
      </div>
    </FieldWrapper>
  );
}
