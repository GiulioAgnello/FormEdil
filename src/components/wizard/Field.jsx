import TextField from './fields/TextField';
import DateField from './fields/DateField';
import SelectField from './fields/SelectField';
import RadioField from './fields/RadioField';
import CheckboxGroup from './fields/CheckboxGroup';
import TextArea from './fields/TextArea';
import Acknowledgment from './fields/Acknowledgment';
import Repeater from './fields/Repeater';
import ParticipantsTable from './fields/ParticipantsTable';
import TeachersTable from './fields/TeachersTable';
import { resolveOptions } from '@/utils/schema';

/**
 * Smista un campo dello schema al componente giusto in base al type.
 * Aggiungere un nuovo tipo = aggiungere un case qui + il componente.
 */
export default function Field({ schema, field, value, error, onChange }) {
  const common = { field, value, error, onChange };

  switch (field.type) {
    case 'date':
    case 'time':
      return <DateField {...common} />;
    case 'select':
      return <SelectField {...common} options={resolveOptions(schema, field)} />;
    case 'radio':
      return <RadioField {...common} options={resolveOptions(schema, field)} />;
    case 'checkboxGroup':
      return <CheckboxGroup {...common} options={resolveOptions(schema, field)} />;
    case 'textarea':
      return <TextArea {...common} />;
    case 'acknowledgment':
      return <Acknowledgment {...common} />;
    case 'repeater':
      return <Repeater {...common} />;
    case 'partecipantiTable':
      return <ParticipantsTable {...common} />;
    case 'docentiTable':
      return <TeachersTable {...common} />;
    case 'text':
    case 'email':
    case 'tel':
    default:
      return <TextField {...common} />;
  }
}
