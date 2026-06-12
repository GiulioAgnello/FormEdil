import FieldWrapper from './FieldWrapper';

/** Menu a tendina. options: [{value,label}]. */
export default function SelectField({ field, value, error, options, onChange }) {
  const id = `f-${field.name}`;
  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <select
        id={id}
        className="input"
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
      >
        <option value="">— Seleziona —</option>
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </FieldWrapper>
  );
}
