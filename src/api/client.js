/**
 * Client API centralizzato.
 * Tutte le chiamate al backend FORMEDIL passano da qui: un solo punto in cui
 * gestire base URL, header, errori e (in futuro) il token JWT dell'admin.
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

export const api = {
  health: () => request('/health'),
  getSchema: (variante) =>
    request(`/schema${variante ? `?variante=${encodeURIComponent(variante)}` : ''}`),

  // Endpoint previsti nei prossimi sprint (placeholder di interfaccia):
  // creaRichiesta: (payload) => request('/richieste', { method: 'POST', body: payload }),
  // getRichiestaByToken: (token) => request(`/richieste/${token}`),
  // inviaDocumentazione: (token, formData) => ... (multipart, S3),
};
