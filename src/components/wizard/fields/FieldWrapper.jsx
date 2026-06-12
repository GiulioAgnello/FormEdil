/** Contenitore comune: label, help, messaggio d'errore. */
export default function FieldWrapper({ field, error, children, htmlFor }) {
  return (
    <div className={`field ${error ? 'field--error' : ''}`}>
      {field.label ? (
        <label className="field__label" htmlFor={htmlFor}>
          {field.label}
          {field.required ? <span className="field__req"> *</span> : null}
        </label>
      ) : null}
      {field.help ? <p className="field__help">{field.help}</p> : null}
      {children}
      {error ? <p className="field__error">{error}</p> : null}
    </div>
  );
}
