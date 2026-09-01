import FieldWrapper from './FieldWrapper';

/** Campo testo / email / tel / numero. Supporta readOnly con valore fisso (field.fixedValue). */
export default function TextField({ field, value, error, onChange }) {
  const id = `f-${field.name}`;
  const type = field.type === 'email' ? 'email' : field.type === 'tel' ? 'tel' : 'text';
  const readOnly = !!field.readOnly;
  const shown = readOnly ? field.fixedValue ?? value ?? '' : value || '';
  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <input
        id={id}
        type={type}
        className="input"
        value={shown}
        placeholder={field.placeholder || ''}
        onChange={(e) => !readOnly && onChange(e.target.value)}
        readOnly={readOnly}
        tabIndex={readOnly ? -1 : undefined}
        autoComplete="off"
      />
    </FieldWrapper>
  );
}
