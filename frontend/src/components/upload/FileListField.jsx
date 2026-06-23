import { useRef } from 'react';

/**
 * Campo per più file (allegati liberi): aggiunta incrementale + rimozione.
 * Riutilizzabile e senza logica di dominio.
 *
 * props:
 *  - label, hint, accept, files (File[]), onChange(File[]), error
 */
export default function FileListField({ label, hint, accept, files, onChange, error }) {
  const inputRef = useRef(null);

  function add(selected) {
    if (!selected || selected.length === 0) return;
    const next = [...files];
    // Evita duplicati per nome+dimensione.
    for (const f of selected) {
      if (!next.some((x) => x.name === f.name && x.size === f.size)) next.push(f);
    }
    onChange(next);
    if (inputRef.current) inputRef.current.value = '';
  }

  function remove(i) {
    onChange(files.filter((_, idx) => idx !== i));
  }

  return (
    <div className="upload-field">
      <span className="upload-field__label">{label}</span>

      {files.length > 0 ? (
        <ul className="upload-list">
          {files.map((f, i) => (
            <li key={`${f.name}-${f.size}-${i}`} className="upload-list__item">
              <span className="upload-list__name">{f.name}</span>
              <button type="button" className="upload-list__remove" onClick={() => remove(i)}>
                Rimuovi
              </button>
            </li>
          ))}
        </ul>
      ) : null}

      <button
        type="button"
        className="btn btn--ghost upload-list__add"
        onClick={() => inputRef.current?.click()}
      >
        + Aggiungi allegato
      </button>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple
        className="upload-drop__input"
        onChange={(e) => add(e.target.files)}
      />

      {hint ? <span className="upload-field__hint">{hint}</span> : null}
      {error ? <span className="upload-field__error">{error}</span> : null}
    </div>
  );
}
