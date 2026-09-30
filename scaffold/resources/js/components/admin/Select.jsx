import { useId } from 'react';
import Field, { controlClasses } from './Field';

// options : [{ value, label }]
export default function Select({
    label,
    error,
    hint,
    id,
    required,
    className = '',
    options = [],
    placeholder = 'Sélectionner…',
    ...props
}) {
    const autoId = useId();
    const selectId = id ?? autoId;

    return (
        <Field id={selectId} label={label} error={error} hint={hint} required={required} className={className}>
            <select id={selectId} required={required} className={controlClasses(error)} {...props}>
                {placeholder !== false && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Field>
    );
}
