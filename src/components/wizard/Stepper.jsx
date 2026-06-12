/** Indicatore visivo degli step. Cliccabile sugli step già visitati. */
export default function Stepper({ steps, current, maxReached, onGo }) {
  return (
    <ol className="stepper">
      {steps.map((s, i) => {
        const state = i === current ? 'active' : i < current ? 'done' : 'todo';
        const clickable = i <= maxReached;
        return (
          <li key={s.id} className={`stepper__item stepper__item--${state}`}>
            <button
              type="button"
              className="stepper__btn"
              disabled={!clickable}
              onClick={() => clickable && onGo(i)}
            >
              <span className="stepper__num">{i + 1}</span>
              <span className="stepper__label">{s.title}</span>
            </button>
          </li>
        );
      })}
    </ol>
  );
}
