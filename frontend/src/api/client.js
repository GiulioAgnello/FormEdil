/**
 * Client API centralizzato.
 * Tutte le chiamate al backend FORMEDIL passano da qui: un solo punto in cui
 * gestire base URL, header ed errori.
 */
import { API_BASE } from '@/config';

async function request(path, { method = 'GET', body, headers = {}, signal } = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...headers,
    },
    body: body ? JSON.stringify(body) : undefined,
    signal,
  });

  let data = null;
  try {
    data = await res.json();
  } catch {
    // Risposta senza corpo JSON: lasciamo data a null.
  }

  if (!res.ok) {
    const message = data?.message || `Errore ${res.status}`;
    const err = new Error(message);
    err.status = res.status;
    err.payload = data;
    throw err;
  }

  return data;
}

/**
 * Invio multipart (upload file). Non impostiamo Content-Type: il browser
 * aggiunge da solo il boundary corretto per il FormData.
 */
async function upload(path, formData, { signal } = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    method: 'POST',
    body: formData,
    signal,
  });

  let data = null;
  try {
    data = await res.json();
  } catch {
    // Risposta senza corpo JSON.
  }

  if (!res.ok) {
    const message = data?.message || `Errore ${res.status}`;
    const err = new Error(message);
    err.status = res.status;
    err.payload = data;
    throw err;
  }

  return data;
}

export const api = {
  health: () => request('/health'),
  getSchema: (variante) =>
    request(`/schema${variante ? `?variante=${encodeURIComponent(variante)}` : ''}`),

  /** Crea una richiesta: { variante, dati } -> { token, pdf_url, invio_url }. */
  creaRichiesta: (variante, dati) =>
    request('/richieste', { method: 'POST', body: { variante, dati } }),

  /** Riepilogo minimo per token (pagina di invio / esito). */
  getRichiestaByToken: (token) => request(`/richieste/${encodeURIComponent(token)}`),

  /** URL diretto per il download del PDF. */
  pdfUrl: (token) => `${API_BASE}/richieste/${encodeURIComponent(token)}/pdf`,

  /**
   * Invia PDF firmato + allegati (S3).
   * @param {string} token
   * @param {{ firmato: File, allegati?: File[] }} files
   */
  inviaDocumentazione: (token, { firmato, allegati = [] }, opts = {}) => {
    const fd = new FormData();
    fd.append('firmato', firmato);
    allegati.forEach((f) => fd.append('allegati[]', f));
    return upload(`/richieste/${encodeURIComponent(token)}/invio`, fd, opts);
  },
};
