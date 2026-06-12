import { useState, useMemo, useEffect, useCallback } from 'react';
import {
  stepsForVariant,
  activeFields,
  initialData,
} from '@/utils/schema';
import { validateField } from '@/utils/validators';
import { saveBozza, loadBozza } from '@/utils/storage';

/**
 * Stato e logica del wizard: dati, step corrente, validazione per-step,
 * autosave. I componenti si limitano a renderizzare.
 */
export function useWizard(schema, variante) {
  const steps = useMemo(() => stepsForVariant(schema, variante), [schema, variante]);

  const [dati, setDati] = useState(() => {
    const base = initialData(schema, variante);
    const bozza = loadBozza(variante);
    return bozza ? { ...base, ...bozza } : base;
  });
  const [stepIndex, setStepIndex] = useState(0);
  const [errors, setErrors] = useState({});

  // Autosave ad ogni modifica (debounce leggero).
  useEffect(() => {
    const t = setTimeout(() => saveBozza(variante, dati), 400);
    return () => clearTimeout(t);
  }, [variante, dati]);

  const setField = useCallback((name, value) => {
    setDati((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => {
      if (!prev[name]) return prev;
      const next = { ...prev };
      delete next[name];
      return next;
    });
  }, []);

  const currentStep = steps[stepIndex];
  const currentFields = useMemo(
    () => (currentStep ? activeFields(currentStep, variante, dati) : []),
    [currentStep, variante, dati]
  );

  /** Valida lo step corrente; popola errors. Ritorna true se valido. */
  const validateCurrent = useCallback(() => {
    const stepErrors = {};
    for (const field of currentFields) {
      const err = validateField(field, dati[field.name]);
      if (err) stepErrors[field.name] = err;
    }
    setErrors(stepErrors);
    return Object.keys(stepErrors).length === 0;
  }, [currentFields, dati]);

  const next = useCallback(() => {
    if (!validateCurrent()) return false;
    setStepIndex((i) => Math.min(i + 1, steps.length - 1));
    return true;
  }, [validateCurrent, steps.length]);

  const prev = useCallback(() => {
    setErrors({});
    setStepIndex((i) => Math.max(i - 1, 0));
  }, []);

  const goTo = useCallback((i) => {
    setErrors({});
    setStepIndex(() => Math.max(0, Math.min(i, steps.length - 1)));
  }, [steps.length]);

  return {
    steps,
    stepIndex,
    currentStep,
    currentFields,
    dati,
    errors,
    setErrors,
    setField,
    setStepIndex,
    validateCurrent,
    next,
    prev,
    goTo,
    isFirst: stepIndex === 0,
    isLast: stepIndex === steps.length - 1,
  };
}
