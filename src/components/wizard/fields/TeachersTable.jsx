import FieldWrapper from './FieldWrapper';

/**
 * Tabella docenti (max 4). value = array di { nome, cognome, data_nascita }.
 */
export default function TeachersTable({ field, value, error, onChange }) {
  const cols = field.itemFields || [];
  const max = field.max || 4;
  const emptyRow = () => Object.fromEntries(cols.map((c) => [c.name, '']));
  const rows = Array.isArray(value) && value.length ? value : [emptyRow()];

  const setCell = (i, name, v) =>
    onChange(rows.map((r, idx) => (idx === i ? { ...r, [name]: v } : r)));
  const addRow = () => rows.length < max && onChange([...rows, emptyRow()]);
  const removeRow = (i) => onChange(rows.filter((_, idx) => idx !== i));

  return (
    <FieldWrapper field={field} error={error}>
      <table className="ptable__grid">
        <thead>
          <tr>
            <th style={{ width: '8%' }}>N.</th>
            {cols.map((c) => (
              <th key={c.name}>{c.label}</th>
            ))}
            <th style={{ width: '6%' }}></th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row, i) => (
            <tr key={i}>
              <td className="ptable__num">{i + 1}</td>
              {cols.map((c) => (
                <td key={c.name}>
                  <input
                    type={c.type === 'date' ? 'date' : 'text'}
                    className="input input--cell"
                    value={row[c.name] || ''}
                    onChange={(e) => setCell(i, c.name, e.target.value)}
                  />
                </td>
              ))}
              <td>
                <button
                  type="button"
                  className="repeater__remove"
                  onClick={() => removeRow(i)}
                  aria-label="Rimuovi"
                >
                  ✕
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <button
        type="button"
        className="btn btn--ghost btn--sm"
        onClick={addRow}
        disabled={rows.length >= max}
      >
        + Aggiungi docente
      </button>
    </FieldWrapper>
  );
}
