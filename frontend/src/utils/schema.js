/**
 * Helper per interrogare lo schema in funzione della variante e dello stato.
 */

/** Step applicabili a una variante (IMPRESA/ENTE). */
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
  if (field.readOnly && field.fixedValue !== undefined) return field.fixedValue;
  switch (field.type) {
    case 'checkboxGroup':
      return [];
    case 'acknowledgment':
      return false;
    case 'repeater':
    case 'impreseRepeater':
    case 'partecipantiTable':
    case 'docentiTable':
      return [];
    case 'provinciaComuneCap':
      return { provincia: '', provincia_nome: '', comune: '', cap: '' };
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

/**
 * Riallinea i campi readOnly+fixedValue (es. ATECO) al valore fisso dello
 * schema, anche dentro le righe di un impreseRepeater. Serve perché una
 * bozza salvata prima dell'introduzione di un campo fisso può ancora
 * contenere un valore libero vecchio.
 */
export function applyFixedValues(schema, variante, dati) {
  const next = { ...dati };
  for (const step of stepsForVariant(schema, variante)) {
    for (const field of step.fields || []) {
      if (field.variants && !field.variants.includes(variante)) continue;
      if (field.readOnly && field.fixedValue !== undefined) {
        next[field.name] = field.fixedValue;
        continue;
      }
      if (field.type === 'impreseRepeater' && Array.isArray(next[field.name])) {
        const itemFields = field.itemFields || [];
        next[field.name] = next[field.name].map((item) => {
          let changed = false;
          const patched = { ...item };
          for (const f of itemFields) {
            if (f.readOnly && f.fixedValue !== undefined && patched[f.name] !== f.fixedValue) {
              patched[f.name] = f.fixedValue;
              changed = true;
            }
          }
          return changed ? patched : item;
        });
      }
    }
  }
  return next;
}
