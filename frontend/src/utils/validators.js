import { fieldActive } from '@/utils/schema';

/**
 * Validatori lato client — mirror della logica backend.
 * La fonte di verità resta il server (che rivalida sempre): qui diamo solo
 * feedback immediato all'utente durante la compilazione.
 */

const CF_ODD = {
  0: 1, 1: 0, 2: 5, 3: 7, 4: 9, 5: 13, 6: 15, 7: 17, 8: 19, 9: 21,
  A: 1, B: 0, C: 5, D: 7, E: 9, F: 13, G: 15, H: 17, I: 19, J: 21, K: 2,
  L: 4, M: 18, N: 20, O: 11, P: 3, Q: 6, R: 8, S: 12, T: 14, U: 16, V: 10,
  W: 22, X: 25, Y: 24, Z: 23,
};
const CF_EVEN = {
  0: 0, 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 7, 8: 8, 9: 9,
  A: 0, B: 1, C: 2, D: 3, E: 4, F: 5, G: 6, H: 7, I: 8, J: 9, K: 10,
  L: 11, M: 12, N: 13, O: 14, P: 15, Q: 16, R: 17, S: 18, T: 19, U: 20,
  V: 21, W: 22, X: 23, Y: 24, Z: 25,
};
const CF_REMAINDER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

/** Codice Fiscale persona fisica con carattere di controllo. */
export function isValidCF(value) {
  const cf = String(value || '').toUpperCase().trim();
  if (!/^[A-Z0-9]{16}$/.test(cf)) return false;
  let sum = 0;
  for (let i = 0; i < 15; i++) {
    const c = cf[i];
    sum += (i + 1) % 2 === 1 ? CF_ODD[c] : CF_EVEN[c];
  }
  return cf[15] === CF_REMAINDER[sum % 26];
}

/** Partita IVA: 11 cifre con checksum (Luhn pari/dispari). */
export function isValidPiva(value) {
  const p = String(value || '').trim();
  if (!/^[0-9]{11}$/.test(p)) return false;
  let sum = 0;
  for (let i = 0; i < 11; i++) {
    let n = Number(p[i]);
    if (i % 2 === 1) {
      n *= 2;
      if (n > 9) n -= 9;
    }
    sum += n;
  }
  return sum % 10 === 0;
}

export function isEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
}

/** Applica la regola di formato dichiarata nello schema. */
export function checkFormat(rule, value) {
  switch (rule) {
    case 'cf':
      return isValidCF(value) ? null : 'Codice Fiscale non valido.';
    case 'piva':
      return isValidPiva(value) ? null : 'Partita IVA non valida.';
    case 'email':
      return isEmail(value) ? null : 'Email non valida.';
    default:
      return null;
  }
}

const isEmpty = (v) =>
  v === null || v === undefined || (typeof v === 'string' && v.trim() === '');

/**
 * Valida un singolo campo. Restituisce messaggio d'errore o null.
 * @param {object} field definizione dello schema
 * @param {*} value valore corrente
 */
export function validateField(field, value) {
  const type = field.type || 'text';

  switch (type) {
    case 'checkboxGroup': {
      const count = Array.isArray(value) ? value.length : 0;
      const min = field.min ?? (field.required ? 1 : 0);
      return count < min ? `Selezionare almeno ${Math.max(1, min)} opzione/i.` : null;
    }
    case 'acknowledgment':
      return field.required && value !== true
        ? 'È necessario accettare questa dichiarazione.'
        : null;

    case 'impreseRepeater': {
      const items = Array.isArray(value) ? value : [];
      const min = field.min ?? 1;
      const max = field.max ?? null;
      if (items.length < min) return `Inserire almeno ${min} impresa/e.`;
      if (max !== null && items.length > max) return `Massimo ${max} imprese consentite.`;
      const itemFields = field.itemFields || [];
      for (const it of items) {
        const active = itemFields.filter((f) => fieldActive(f, 'ENTE', it));
        if (Object.keys(validateRow(active, it)).length > 0) {
          return 'Completa i dati di tutte le imprese inserite.';
        }
      }
      return null;
    }

    case 'repeater':
    case 'partecipantiTable':
    case 'docentiTable':
      return validateCollection(field, Array.isArray(value) ? value : []);

    case 'provinciaComuneCap': {
      if (!field.required) return null;
      const v = value && typeof value === 'object' ? value : {};
      if (isEmpty(v.provincia) || isEmpty(v.comune)) return 'Selezionare provincia e comune.';
      if (isEmpty(v.cap)) return 'Indicare il CAP.';
      return null;
    }

    default: {
      if (isEmpty(value)) return field.required ? 'Campo obbligatorio.' : null;
      return checkFormat(field.validation, value);
    }
  }
}

function validateCollection(field, items) {
  const min = field.min ?? 1;
  const max = field.max ?? null;
  if (items.length < min) return `Inserire almeno ${min} voce/i.`;
  if (max !== null && items.length > max) return `Massimo ${max} voci consentite.`;
  // Le righe vengono validate per cella dai componenti tabella; qui solo i conteggi.
  return null;
}

/**
 * Valida una riga di tabella/repeater, ritorna mappa { subName: errore }.
 */
export function validateRow(itemFields, row) {
  const errors = {};
  for (const sub of itemFields) {
    const val = row?.[sub.name];
    if (sub.type === 'provinciaComuneCap') {
      const o = val && typeof val === 'object' ? val : {};
      if (sub.required && (isEmpty(o.provincia) || isEmpty(o.comune) || isEmpty(o.cap))) {
        errors[sub.name] = 'Provincia, comune e CAP obbligatori.';
      }
      continue;
    }
    if (isEmpty(val)) {
      if (sub.required) errors[sub.name] = 'Obbligatorio.';
      continue;
    }
    const err = checkFormat(sub.validation, val);
    if (err) errors[sub.name] = err;
  }
  return errors;
}
