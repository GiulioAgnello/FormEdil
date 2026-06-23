/** Casella di presa visione / dichiarazione (boolean obbligatorio). */
export default function Acknowledgment({ field, value, error, onChange }) {
  return (
    <div className={`field ack ${error ? 'field--error' : ''}`}>
      <label className="option option--ack">
        <input
          type="checkbox"
          checked={value === true}
          onChange={(e) => onChange(e.target.checked)}
        />
        <span>
          {field.label}
          {field.required ? <span className="field__req"> *</span> : null}
        </span>
      </label>
      {error ? <p className="field__error">{error}</p> : null}
    </div>
  );
}
