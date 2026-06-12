/**
 * Helper per interrogare lo schema in funzione della variante e dello stato.
 */

/** Step applicabili a una variante (DTL/ENTE). */
export function stepsForVariant(schema, variante) {
  return (schema.steps || []).filter(
    (s) => !s.variants || s.variants.includes(variante)
  );
}

/** Un campo è attivo per la variante? */
export function fieldInVariant(field, variante) {
  return !field.variants || field.variants.includes(variante);
}

/** La condizione del campo è soddisfatta dallo stato corrente? */
export function conditionMet(condition, dati) {
  if (!condition) return true;
  const current = dati[condition.field];
  if ('includes' in condition) {
    return Array.isArray(current) && current.includes(condition.includes);
  }
  if ('equals' in condition) {
    return current === condition.equals;
  }
  return true;
}

/** Il campo va mostrato/validato ora? (variante + condizione) */
export function fieldActive(field, variante, dati) {
  return fieldInVariant(field, variante) && conditionMet(field.condition, dati);
}

/** Campi attivi di uno step. */
export function activeFields(step, variante, dati) {
  return (step.fields || []).filter((f) => fieldActive(f, variante, dati));
}

/** Risolve le opzioni di un campo (optionsRef -> schema.options, oppure inline). */
export function resolveOptions(schema, field) {
  if (field.optionsRef) return schema.options?.[field.optionsRef] || [];
  return field.options || [];
}

/** Valore iniziale coerente col tipo di campo. */
export function emptyValue(field) {
  switch (field.type) {
    case 'checkboxGroup':
      return [];
    case 'acknowledgment':
      return false;
    case 'repeater':
    case 'partecipantiTable':
    case 'docentiTable':
      return [];
    default:
      return '';
  }
}

/** Stato iniziale completo per una variante. */
export function initialData(schema, variante) {
  const data = {};
  for (const step of stepsForVariant(schema, variante)) {
    for (const field of step.fields || []) {
      if (field.variants && !field.variants.includes(variante)) continue;
      data[field.name] = emptyValue(field);
    }
  }
  return data;
}
