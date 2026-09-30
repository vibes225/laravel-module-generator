import { Trash2 } from 'lucide-react';
import { Button, Choice, Labeled, NumberInput, Text, Toggle } from './form';

const FLAGS = [
    ['searchable', 'Recherche'],
    ['sortable', 'Tri'],
    ['filterable', 'Filtre'],
];

const DISPLAY = [
    ['in_table', 'Tableau'],
    ['in_form', 'Formulaire'],
    ['in_detail', 'Fiche'],
];

/** Nouveau champ d'un type donné, avec les drapeaux par défaut exposés par le type. */
export function newField(type, types, pivot = false) {
    const defaults = pivot ? {} : types[type]?.defaults ?? {};

    return { name: '', label: '', type, nullable: false, options: {}, ...defaults };
}

function OptionInput({ schema, value, onChange, errors }) {
    switch (schema.type) {
        case 'int':
            return <NumberInput label={schema.label} value={value} placeholder={schema.default} onChange={onChange} errors={errors} />;
        case 'bool':
            return <Toggle label={schema.label} checked={value ?? schema.default} onChange={onChange} errors={errors} />;
        case 'select':
            return (
                <Choice
                    label={schema.label}
                    value={value ?? schema.default}
                    onChange={onChange}
                    errors={errors}
                    options={schema.choices.map((choice) => ({ value: choice, label: choice }))}
                />
            );
        case 'list':
            return (
                <Text
                    label={schema.label}
                    value={(value ?? schema.default ?? []).join(', ')}
                    onChange={(text) => onChange(text.split(',').map((item) => item.trim()).filter(Boolean))}
                    errors={errors}
                />
            );
        case 'enum_values':
            return (
                <Labeled label={`${schema.label} (une par ligne : valeur ou valeur:Libellé)`} errors={errors} className="sm:col-span-2">
                    <textarea
                        rows={3}
                        className="block w-full rounded-md border border-slate-300 px-2.5 py-1.5 font-mono text-sm"
                        value={(value ?? []).map((item) => (typeof item === 'string' ? item : `${item.value}:${item.label}`)).join('\n')}
                        onChange={(event) =>
                            onChange(
                                event.target.value
                                    .split('\n')
                                    .map((line) => line.trim())
                                    .filter(Boolean)
                                    .map((line) => {
                                        const [item, ...label] = line.split(':');

                                        return label.length ? { value: item.trim(), label: label.join(':').trim() } : item.trim();
                                    }),
                            )
                        }
                    />
                </Labeled>
            );
        default:
            return <Text label={schema.label} value={value} placeholder={schema.default} onChange={onChange} errors={errors} />;
    }
}

function DefaultInput({ field, type, onChange, errors }) {
    const set = (value) => onChange(value === '' ? undefined : value);

    if (field.type === 'boolean') {
        const value = field.default === undefined || field.default === null ? '' : String(field.default);

        return (
            <Choice
                label="Défaut"
                value={value}
                empty="—"
                errors={errors}
                options={[{ value: 'true', label: 'Oui' }, { value: 'false', label: 'Non' }]}
                onChange={(v) => set(v === '' ? '' : v === 'true')}
            />
        );
    }

    if (field.type === 'enum') {
        const values = (field.options?.values ?? []).map((item) => (typeof item === 'string' ? item : item.value));

        return <Choice label="Défaut" value={field.default} empty="—" errors={errors} options={values.map((v) => ({ value: v, label: v }))} onChange={set} />;
    }

    if (['integer', 'bigInteger', 'decimal'].includes(field.type)) {
        return <NumberInput label="Défaut" value={field.default} onChange={onChange} errors={errors} />;
    }

    const html = { date: 'date', datetime: 'datetime-local', time: 'time' }[field.type] ?? 'text';

    return (
        <Text
            label="Défaut"
            type={html}
            value={typeof field.default === 'string' ? field.default.replace(' ', 'T') : field.default}
            onChange={(value) => set(html === 'datetime-local' ? value.replace('T', ' ') : value)}
            errors={errors}
            hint={type.name === 'string' ? undefined : null}
        />
    );
}

/**
 * Éditeur d'un champ. `pivot` : champ supplémentaire d'une table pivot (pas de drapeaux de présentation,
 * types limités). `errors(path)` renvoie les messages du moteur pour un chemin relatif au champ.
 */
export default function FieldEditor({ field, types, onChange, onRemove, errors, pivot = false }) {
    const type = types[field.type] ?? { options: {}, allows: [], label: field.type };
    const set = (key, value) => onChange({ ...field, [key]: value });
    const setOption = (key, value) => onChange({ ...field, options: { ...field.options, [key]: value } });
    const choices = Object.values(types)
        .filter((item) => !pivot || item.pivot)
        .map((item) => ({ value: item.name, label: item.label }));

    const changeType = (name) => {
        const next = newField(name, types, pivot);
        onChange({ ...next, name: field.name, label: field.label, nullable: field.nullable });
    };

    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
            <div className="grid gap-3 sm:grid-cols-4">
                <Text label="Nom (colonne)" value={field.name} placeholder="name" onChange={(v) => set('name', v)} errors={errors('name')} />
                <Text label="Libellé" value={field.label} placeholder="dérivé du nom" onChange={(v) => set('label', v)} errors={errors('label')} />
                <Choice label="Type" value={field.type} options={choices} onChange={changeType} errors={errors('type')} />
                <div className="flex items-end justify-end">
                    <Button variant="ghost" onClick={onRemove} aria-label="Retirer le champ">
                        <Trash2 className="h-4 w-4 text-red-600" />
                    </Button>
                </div>
            </div>

            {Object.keys(type.options).length > 0 && (
                <div className="mt-3 grid gap-3 sm:grid-cols-4">
                    {Object.entries(type.options).map(([key, schema]) => (
                        <OptionInput
                            key={key}
                            schema={schema}
                            value={field.options?.[key]}
                            onChange={(value) => setOption(key, value)}
                            errors={errors(`options.${key}`)}
                        />
                    ))}
                </div>
            )}

            <div className="mt-3 flex flex-wrap items-end gap-x-5 gap-y-2">
                <Toggle label="Facultatif" checked={field.nullable} onChange={(v) => set('nullable', v)} errors={errors('nullable')} />
                {type.allows.includes('unique') && <Toggle label="Unique" checked={field.unique} onChange={(v) => set('unique', v)} errors={errors('unique')} />}
                {type.allows.includes('index') && <Toggle label="Index" checked={field.index} onChange={(v) => set('index', v)} errors={errors('index')} />}
                {!pivot &&
                    FLAGS.filter(([key]) => type.allows.includes(key)).map(([key, label]) => (
                        <Toggle key={key} label={label} checked={field[key]} onChange={(v) => set(key, v)} errors={errors(key)} />
                    ))}
                {!pivot &&
                    DISPLAY.map(([key, label]) => (
                        <Toggle key={key} label={label} checked={field[key]} onChange={(v) => set(key, v)} errors={errors(key)} />
                    ))}
                {type.allows.includes('default') && (
                    <div className="w-44">
                        <DefaultInput field={field} type={type} onChange={(v) => set('default', v)} errors={errors('default')} />
                    </div>
                )}
            </div>
        </div>
    );
}
