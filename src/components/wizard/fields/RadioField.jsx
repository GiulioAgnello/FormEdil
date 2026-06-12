import FieldWrapper from './FieldWrapper';

/** Gruppo di radio (scelta singola). */
export default function RadioField({ field, value, error, options, onChange }) {
  return (
    <FieldWrapper field={field} error={error}>
      <div className="options">
        {options.map((o) => (
          <label key={o.value} className="option">
            <input
              type="radio"
              name={field.name}
              value={o.value}
              checked={value === o.value}
              onChange={() => onChange(o.value)}
            />
            <span>{o.label}</span>
          </label>
        ))}
      </div>
    </FieldWrapper>
  );
}
