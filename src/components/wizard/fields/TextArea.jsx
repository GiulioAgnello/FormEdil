import FieldWrapper from './FieldWrapper';

/** Area di testo multiriga. */
export default function TextArea({ field, value, error, onChange }) {
  const id = `f-${field.name}`;
  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <textarea
        id={id}
        className="input"
        rows={field.rows || 5}
        value={value || ''}
        placeholder={field.placeholder || ''}
        onChange={(e) => onChange(e.target.value)}
      />
    </FieldWrapper>
  );
}
