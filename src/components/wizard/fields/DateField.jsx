import FieldWrapper from './FieldWrapper';

/** Campo data o ora. */
export default function DateField({ field, value, error, onChange }) {
  const id = `f-${field.name}`;
  const type = field.type === 'time' ? 'time' : 'date';
  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <input
        id={id}
        type={type}
        className="input"
        value={value || ''}
        onChange={(e) => onChange(e.target.value)}
      />
    </FieldWrapper>
  );
}
