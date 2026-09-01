/**
 * Configurazione centralizzata dell'app.
 *
 * In produzione impostare VITE_API_BASE nell'ambiente di build. Il fallback qui
 * sotto punta al backend WordPress reale (installato in /cms sullo stesso
 * dominio della SPA), così un build senza .env non rompe l'app.
 */
export const API_BASE =
  import.meta.env.VITE_API_BASE ||
  'https://gestionale.formedillecce.it/cms/wp-json/formedil/v1';

export const VARIANTI = {
  IMPRESA: 'IMPRESA',
  ENTE: 'ENTE',
};

/** Chiave per l'autosave della bozza in localStorage (sprint S2). */
export const STORAGE_KEY_BOZZA = 'formedil:bozza';
