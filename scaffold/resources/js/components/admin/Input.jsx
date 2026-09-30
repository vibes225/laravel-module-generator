import { useId } from 'react';
import Field, { controlClasses } from './Field';

export default function Input({ label, error, hint, id, required, className = '', type = 'text', ...props }) {
    const autoId = useId();
    const inputId = id ?? autoId;

    return (
        <Field id={inputId} label={label} error={error} hint={hint} required={required} className={className}>
            <input id={inputId} type={type} required={required} className={controlClasses(error)} {...props} />
        </Field>
    );
}
