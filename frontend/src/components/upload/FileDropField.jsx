import { useRef, useState } from 'react';

/**
 * Campo per un singolo file con drag & drop + click.
 * Riutilizzabile: non conosce il significato del file, solo la selezione.
 *
 * props:
 *  - label, hint, accept, file (File|null), onSelect(File|null), error, required
 */
export default function FileDropField({
  label,
  hint,
  accept,
  file,
  onSelect,
  error,
  required = false,
}) {
  const inputRef = useRef(null);
  const [dragging, setDragging] = useState(false);

  function pick(files) {
    if (files && files.length > 0) onSelect(files[0]);
  }

  function onDrop(e) {
    e.preventDefault();
    setDragging(false);
    pick(e.dataTransfer.files);
  }

  return (
    <div className="upload-field">
      <span className="upload-field__label">
        {label} {required ? <em className="upload-field__req">*</em> : null}
      </span>

      <div
        className={`upload-drop${dragging ? ' is-dragging' : ''}${error ? ' has-error' : ''}`}
        onDragOver={(e) => {
          e.preventDefault();
          setDragging(true);
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        onClick={() => inputRef.current?.click()}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') inputRef.current?.click();
        }}
      >
        <input
          ref={inputRef}
          type="file"
          accept={accept}
          className="upload-drop__input"
          onChange={(e) => pick(e.target.files)}
        />
        {file ? (
          <div className="upload-drop__file">
            <span className="upload-drop__name">{file.name}</span>
            <button
              type="button"
              className="upload-drop__remove"
              onClick={(e) => {
                e.stopPropagation();
                onSelect(null);
                if (inputRef.current) inputRef.current.value = '';
              }}
            >
              Rimuovi
            </button>
          </div>
        ) : (
          <span className="upload-drop__placeholder">
            Trascina qui il file o <span className="upload-drop__cta">scegli dal computer</span>
          </span>
        )}
      </div>

      {hint ? <span className="upload-field__hint">{hint}</span> : null}
      {error ? <span className="upload-field__error">{error}</span> : null}
    </div>
  );
}
