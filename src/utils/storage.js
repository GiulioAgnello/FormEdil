/**
 * Autosave della bozza in localStorage.
 * Una bozza per variante; ripristino al rientro, pulizia dopo l'invio.
 */
import { STORAGE_KEY_BOZZA } from '@/config';

const keyFor = (variante) => `${STORAGE_KEY_BOZZA}:${variante}`;

export function saveBozza(variante, dati) {
  try {
    localStorage.setItem(
      keyFor(variante),
      JSON.stringify({ savedAt: Date.now(), dati })
    );
  } catch {
    // Spazio pieno o storage non disponibile: ignoriamo silenziosamente.
  }
}

export function loadBozza(variante) {
  try {
    const raw = localStorage.getItem(keyFor(variante));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.dati ?? null;
  } catch {
    return null;
  }
}

export function clearBozza(variante) {
  try {
    localStorage.removeItem(keyFor(variante));
  } catch {
    /* no-op */
  }
}
