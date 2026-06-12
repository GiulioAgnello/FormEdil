import FieldWrapper from './FieldWrapper';

/** Gruppo di checkbox (scelta multipla). value = array di value. */
export default function CheckboxGroup({ field, value, error, options, onChange }) {
  const selected = Array.isArray(value) ? value : [];

  const toggle = (val) => {
    if (selected.includes(val)) {
      onChange(selected.filter((v) => v !== val));
    } else {
      onChange([...selected, val]);
    }
  };

  return (
    <FieldWrapper field={field} error={error}>
      <div className="options options--col">
        {options.map((o) => (
          <label key={o.value} className="option">
            <input
              type="checkbox"
              checked={selected.includes(o.value)}
              onChange={() => toggle(o.value)}
            />
            <span>{o.label}</span>
          </label>
        ))}
      </div>
    </FieldWrapper>
  );
}
