import { createContext, useContext, useState } from 'react';

/**
 * Condivide lo step corrente del wizard con componenti al di fuori del
 * wizard stesso (es. il pulsante WhatsApp in AppShell). Il Provider vive
 * a livello di AppShell (App.jsx): il Wizard pubblica il proprio stato
 * tramite setWizardStep, il pulsante lo legge tramite useWizardStep.
 *
 * Valore quando si è dentro il wizard:
 *   { stepIndex, totalSteps, stepTitle, variante }
 * Valore quando non si è dentro il wizard: null.
 */
const WizardStepContext = createContext(null);

export function WizardStepProvider({ children }) {
  const [wizardStep, setWizardStep] = useState(null);
  return (
    <WizardStepContext.Provider value={{ wizardStep, setWizardStep }}>
      {children}
    </WizardStepContext.Provider>
  );
}

/** Usato dal Wizard per pubblicare lo step corrente ad ogni cambiamento. */
export function useSetWizardStep() {
  const ctx = useContext(WizardStepContext);
  return ctx ? ctx.setWizardStep : () => {};
}

/** Usato da componenti esterni (es. WhatsAppButton) per leggere lo step corrente. */
export function useWizardStep() {
  const ctx = useContext(WizardStepContext);
  return ctx ? ctx.wizardStep : null;
}
