import FieldWrapper from './FieldWrapper';
import { isValidCF } from '@/utils/validators';

/**
 * Tabella partecipanti (max 30) con INCOLLA DA EXCEL.
 * Incollando un blocco multi-riga/multi-colonna da un foglio di calcolo, le
 * celle si riempiono automaticamente a partire dalla cella di partenza.
 * Le colonne sono guidate dallo schema (field.itemFields).
 */
export default function ParticipantsTable({ field, value, error, onChange }) {
  const rows = Array.isArray(value) ? value : [];
  const cols = field.itemFields || [];
  const max = field.max || 30;

  const emptyRow = () => Object.fromEntries(cols.map((c) => [c.name, '']));
  const ensureOne = rows.length ? rows : [emptyRow()];

  const norm = (name, v) =>
    name === 'codice_fiscale' ? String(v).toUpperCase().trim() : v;

  const setCell = (i, name, v) => {
    const next = ensureOne.map((r, idx) =>
      idx === i ? { ...r, [name]: norm(name, v) } : r
    );
    onChange(next);
  };

  const addRow = () => {
    if (ensureOne.length >= max) return;
    onChange([...ensureOne, emptyRow()]);
  };
  const removeRow = (i) => onChange(ensureOne.filter((_, idx) => idx !== i));
  const clearAll = () => onChange([emptyRow()]);

  /** Incolla a blocco (Excel/TSV) partendo da (startRow, startCol). */
  const handlePaste = (startRow, startCol, e) => {
    const text = e.clipboardData?.getData('text') ?? '';
    // Solo se è un blocco multi-cella, altrimenti lascia il paste normale.
    if (!/\t|\r|\n/.test(text)) return;
    e.preventDefault();

    const lines = text
      .replace(/\r/g, '')
      .split('\n')
      .filter((l) => l.trim() !== '');

    const next = ensureOne.map((r) => ({ ...r }));
    lines.forEach((line, li) => {
      const cells = line.split('\t');
      const r = startRow + li;
      if (r >= max) return;
      if (!next[r]) next[r] = emptyRow();
      cells.forEach((cellVal, ci) => {
        const col = cols[startCol + ci];
        if (!col) return;
        next[r][col.name] = norm(col.name, cellVal.trim());
      });
    });

    onChange(next.slice(0, max));
  };

  const filled = ensureOne.filter((r) =>
    cols.some((c) => String(r[c.name] || '').trim() !== '')
  ).length;

  return (
    <FieldWrapper field={field} error={error}>
      <div className="ptable">
        <div className="ptable__bar">
          <span className="ptable__count">{filled} / {max} partecipanti</span>
          <span className="ptable__hint">
            Suggerimento: copia le righe da Excel (Nome · Cognome · Codice Fiscale) e incollale qui.
          </span>
          {filled > 0 ? (
            <button type="button" className="btn btn--ghost btn--sm" onClick={clearAll}>
              Svuota
            </button>
          ) : null}
        </div>

        <table className="ptable__grid">
          <thead>
            <tr>
              <th style={{ width: '8%' }}>N.</th>
              {cols.map((c) => (
                <th key={c.name}>{c.label}</th>
              ))}
              <th style={{ width: '6%' }} aria-label="azioni"></th>
            </tr>
          </thead>
          <tbody>
            {ensureOne.map((row, i) => (
              <tr key={i}>
                <td className="ptable__num">{i + 1}</td>
                {cols.map((c, ci) => {
                  const v = row[c.name] || '';
                  const cfInvalid =
                    c.validation === 'cf' && v.trim() !== '' && !isValidCF(v);
                  return (
                    <td key={c.name}>
                      <input
                        className={`input input--cell ${cfInvalid ? 'input--invalid' : ''}`}
                        value={v}
                        onChange={(e) => setCell(i, c.name, e.target.value)}
                        onPaste={(e) => handlePaste(i, ci, e)}
                      />
                    </td>
                  );
                })}
                <td>
                  <button
                    type="button"
                    className="repeater__remove"
                    onClick={() => removeRow(i)}
                    aria-label="Rimuovi riga"
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
          disabled={ensureOne.length >= max}
        >
          + Aggiungi partecipante
        </button>
      </div>
    </FieldWrapper>
  );
}
