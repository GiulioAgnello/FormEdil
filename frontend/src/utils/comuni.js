/**
 * Caricamento lazy del dataset Province/Comuni/CAP.
 * Il file (~310 KB) sta in /public e viene scaricato una sola volta, solo
 * quando serve un campo Provincia→Comune→CAP. La promise è memorizzata così
 * più campi nella stessa pagina condividono un unico download.
 */
let cache = null;

export function loadComuni() {
  if (cache) return cache;
  const url = (import.meta.env.BASE_URL || '/') + 'comuni-italia.json';
  cache = fetch(url)
    .then((res) => {
      if (!res.ok) throw new Error('Dataset comuni non disponibile.');
      return res.json();
    })
    .catch((err) => {
      cache = null; // permetti un nuovo tentativo
      throw err;
    });
  return cache;
}

/** Elenco province [{ sigla, nome }] ordinate. */
export function getProvince(data) {
  return data?.province || [];
}

/** Comuni di una provincia (sigla) [{ nome, cap[] }]. */
export function getComuni(data, sigla) {
  return (data?.comuni && data.comuni[sigla]) || [];
}

/** CAP disponibili per un comune di una provincia. */
export function getCap(data, sigla, comuneNome) {
  const c = getComuni(data, sigla).find((x) => x.nome === comuneNome);
  return c ? c.cap : [];
}
