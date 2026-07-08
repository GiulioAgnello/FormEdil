import FieldWrapper from "./FieldWrapper";

/** Area di testo multiriga. */
export default function TextArea({ field, value, error, onChange }) {
  const id = `f-${field.name}`;
  const sizeClass = field.fontSize ? `input--${field.fontSize}` : "";

  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      <textarea
        id={id}
        className={`input ${sizeClass}`.trim()}
        rows={field.rows || 5}
        value={value || ""}
        placeholder={field.placeholder || ""}
        onChange={(e) => onChange(e.target.value)}
      />
    </FieldWrapper>
  );
}
