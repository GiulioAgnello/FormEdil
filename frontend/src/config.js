/**
 * Configurazione centralizzata dell'app.
 * In produzione impostare VITE_API_BASE nell'ambiente di build.
 */
export const API_BASE =
  import.meta.env.VITE_API_BASE || 'https://moduli.formedillecce.it/wp-json/formedil/v1';

export const VARIANTI = {
  IMPRESA: 'IMPRESA',
  ENTE: 'ENTE',
};

/** Chiave per l'autosave della bozza in localStorage (sprint S2). */
export const STORAGE_KEY_BOZZA = 'formedil:bozza';
