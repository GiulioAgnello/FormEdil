import FieldWrapper from './FieldWrapper';

/**
 * Tabella ripetibile generica basata su itemFields (es. giornate del corso).
 * value = array di oggetti riga.
 */
export default function Repeater({ field, value, error, onChange }) {
  const rows = Array.isArray(value) ? value : [];
  const itemFields = field.itemFields || [];

  const emptyRow = () =>
    Object.fromEntries(itemFields.map((f) => [f.name, '']));

  const addRow = () => onChange([...rows, emptyRow()]);
  const removeRow = (i) => onChange(rows.filter((_, idx) => idx !== i));
  const setCell = (i, name, v) =>
    onChange(rows.map((r, idx) => (idx === i ? { ...r, [name]: v } : r)));

  const inputType = (t) => (t === 'time' ? 'time' : t === 'date' ? 'date' : 'text');

  return (
    <FieldWrapper field={field} error={error}>
      <div className="repeater">
        {rows.length === 0 ? (
          <p className="repeater__empty">Nessuna voce. Aggiungine una.</p>
        ) : (
          rows.map((row, i) => (
            <div key={i} className="repeater__row">
              {itemFields.map((f) => (
                <div key={f.name} className="repeater__cell">
                  <span className="repeater__cellLabel">{f.label}</span>
                  <input
                    type={inputType(f.type)}
                    className="input"
                    value={row[f.name] || ''}
                    onChange={(e) => setCell(i, f.name, e.target.value)}
                  />
                </div>
              ))}
              <button
                type="button"
                className="repeater__remove"
                onClick={() => removeRow(i)}
                aria-label="Rimuovi"
              >
                ✕
              </button>
            </div>
          ))
        )}
        <button type="button" className="btn btn--ghost btn--sm" onClick={addRow}>
          + Aggiungi
        </button>
      </div>
    </FieldWrapper>
  );
}
