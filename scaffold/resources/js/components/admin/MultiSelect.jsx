import { useId, useState } from 'react';
import { X } from 'lucide-react';
import Field from './Field';

/**
 * Sélection multiple (relation belongsToMany) : liste de cases à cocher filtrable.
 * options : [{ value, label }] ; value : tableau des valeurs choisies ; onChange reçoit le nouveau tableau.
 */
export default function MultiSelect({
    label,
    error,
    hint,
    id,
    required,
    className = '',
    options = [],
    value = [],
    onChange,
    disabled = false,
    searchPlaceholder = 'Filtrer…',
}) {
    const autoId = useId();
    const fieldId = id ?? autoId;
    const [query, setQuery] = useState('');

    // Les identifiants peuvent arriver en nombre ou en chaîne : comparaison sur la forme texte.
    const selected = new Set(value.map(String));
    const needle = query.trim().toLowerCase();
    const visible = needle === '' ? options : options.filter((option) => String(option.label).toLowerCase().includes(needle));

    const toggle = (optionValue) => {
        const key = String(optionValue);

        onChange?.(
            selected.has(key)
                ? value.filter((item) => String(item) !== key)
                : [...value, optionValue],
        );
    };

    const chosen = options.filter((option) => selected.has(String(option.value)));

    return (
        <Field id={fieldId} label={label} error={error} hint={hint} required={required} className={className}>
            <div
                className={[
                    'rounded-lg border bg-white shadow-sm',
                    error ? 'border-red-400' : 'border-slate-300',
                    disabled ? 'opacity-60' : '',
                ].join(' ')}
            >
                {chosen.length > 0 && (
                    <div className="flex flex-wrap gap-1.5 border-b border-slate-200 p-2">
                        {chosen.map((option) => (
                            <span
                                key={option.value}
                                className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700"
                            >
                                {option.label}
                                {!disabled && (
                                    <button
                                        type="button"
                                        onClick={() => toggle(option.value)}
                                        className="rounded-full text-indigo-400 hover:text-indigo-700"
                                        aria-label={`Retirer ${option.label}`}
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                )}
                            </span>
                        ))}
                    </div>
                )}
                {options.length > 8 && (
                    <input
                        id={fieldId}
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={searchPlaceholder}
                        disabled={disabled}
                        className="block w-full border-0 border-b border-slate-200 px-3 py-2 text-sm focus:ring-0 focus:outline-none"
                    />
                )}
                <ul className="max-h-56 overflow-y-auto py-1" role="listbox" aria-multiselectable="true">
                    {visible.map((option) => {
                        const checked = selected.has(String(option.value));

                        return (
                            <li key={option.value}>
                                <label className="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        checked={checked}
                                        disabled={disabled}
                                        onChange={() => toggle(option.value)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-200"
                                    />
                                    {option.label}
                                </label>
                            </li>
                        );
                    })}
                    {visible.length === 0 && <li className="px-3 py-2 text-sm text-slate-500">Aucun résultat</li>}
                </ul>
            </div>
        </Field>
    );
}
