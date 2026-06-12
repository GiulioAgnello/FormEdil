import FieldWrapper from './FieldWrapper';

/** Campo testo / email / tel / numero. */
export default function TextField({ field, value, error, onChange }) {
  const id = `f-${field.name}`;
  const type = field.type === 'email' ? 'email' : field.type === 'tel' ? 'tel' : 'text';
  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <input
        id={id}
        type={type}
        className="input"
        value={value || ''}
        placeholder={field.placeholder || ''}
        onChange={(e) => onChange(e.target.value)}
        autoComplete="off"
      />
    </FieldWrapper>
  );
}
