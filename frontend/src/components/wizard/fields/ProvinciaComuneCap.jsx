import { useEffect, useState } from 'react';
import FieldWrapper from './FieldWrapper';
import { loadComuni, getProvince, getComuni, getCap } from '@/utils/comuni';

/**
 * Campo composito Provincia → Comune → CAP con tendine concatenate.
 * Valore: { provincia (sigla), provincia_nome, comune, cap }.
 * Il dataset viene caricato lazy al primo montaggio.
 */
export default function ProvinciaComuneCap({ field, value, error, onChange }) {
  const v = value && typeof value === 'object' ? value : {};
  const [data, setData] = useState(null);
  const [loadErr, setLoadErr] = useState('');

  useEffect(() => {
    let alive = true;
    loadComuni()
      .then((d) => alive && setData(d))
      .catch(() => alive && setLoadErr('Impossibile caricare l’elenco dei comuni.'));
    return () => {
      alive = false;
    };
  }, []);

  const province = getProvince(data);
  const comuni = v.provincia ? getComuni(data, v.provincia) : [];
  const capList = v.provincia && v.comune ? getCap(data, v.provincia, v.comune) : [];

  function setProvincia(sigla) {
    const prov = province.find((p) => p.sigla === sigla);
    onChange({ provincia: sigla, provincia_nome: prov ? prov.nome : '', comune: '', cap: '' });
  }

  function setComune(nome) {
    const caps = getCap(data, v.provincia, nome);
    onChange({ ...v, comune: nome, cap: caps.length === 1 ? caps[0] : '' });
  }

  function setCap(cap) {
    onChange({ ...v, cap });
  }

  const id = `f_${field.name}`;

  return (
    <FieldWrapper field={field} error={error} htmlFor={id}>
      {loadErr ? <p className="field__error">{loadErr}</p> : null}
      <div className="pcc">
        <select
          id={id}
          className="pcc__select"
          value={v.provincia || ''}
          onChange={(e) => setProvincia(e.target.value)}
          disabled={!data}
        >
          <option value="">{data ? 'Provincia…' : 'Caricamento…'}</option>
          {province.map((p) => (
            <option key={p.sigla} value={p.sigla}>
              {p.nome} ({p.sigla})
            </option>
          ))}
        </select>

        <select
          className="pcc__select"
          value={v.comune || ''}
          onChange={(e) => setComune(e.target.value)}
          disabled={!v.provincia}
        >
          <option value="">{v.provincia ? 'Comune…' : 'Scegli prima la provincia'}</option>
          {comuni.map((c) => (
            <option key={c.nome} value={c.nome}>
              {c.nome}
            </option>
          ))}
        </select>

        {capList.length > 1 ? (
          <select
            className="pcc__cap"
            value={v.cap || ''}
            onChange={(e) => setCap(e.target.value)}
          >
            <option value="">CAP…</option>
            {capList.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
        ) : (
          <input
            className="pcc__cap"
            type="text"
            inputMode="numeric"
            placeholder="CAP"
            maxLength={5}
            value={v.cap || ''}
            onChange={(e) => setCap(e.target.value)}
            disabled={!v.comune}
          />
        )}
      </div>
    </FieldWrapper>
  );
}
