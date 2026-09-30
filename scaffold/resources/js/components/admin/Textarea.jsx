import { useId } from 'react';
import Field, { controlClasses } from './Field';

export default function Textarea({ label, error, hint, id, required, className = '', rows = 4, ...props }) {
    const autoId = useId();
    const textareaId = id ?? autoId;

    return (
        <Field id={textareaId} label={label} error={error} hint={hint} required={required} className={className}>
            <textarea id={textareaId} rows={rows} required={required} className={controlClasses(error)} {...props} />
        </Field>
    );
}
