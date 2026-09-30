import { Plus, Trash2 } from 'lucide-react';
import FieldEditor, { newField } from './FieldEditor';
import { Button, Choice, Text, Toggle } from './form';

const TYPES = [
    { value: 'belongsTo', label: 'belongsTo (appartient à)' },
    { value: 'hasOne', label: 'hasOne (a un)' },
    { value: 'hasMany', label: 'hasMany (a plusieurs)' },
    { value: 'belongsToMany', label: 'belongsToMany (plusieurs à plusieurs)' },
];

const ON_DELETE = ['restrict', 'cascade', 'null', 'no_action'].map((value) => ({ value, label: value }));

const PIVOT_MODES = [
    { value: 'none', label: 'Aucune (méthode seule)' },
    { value: 'generate', label: 'Générer la table pivot' },
    { value: 'module', label: 'Utiliser un module pivot existant' },
];

export function newRelation() {
    return { type: 'belongsTo', target: '' };
}

function PivotOptions({ relation, pivot, types, models, onChange, errors }) {
    const setPivot = (key, value) => onChange({ ...relation, pivot: { ...pivot, [key]: value } });
    const fields = pivot.fields ?? [];
    const pivotModules = models.filter((model) => model.kind === 'pivot');

    return (
        <div className="mt-4 space-y-3 rounded-md border border-dashed border-slate-300 p-3">
            <div className="grid gap-3 sm:grid-cols-4">
                <Choice label="Table pivot" value={pivot.mode} options={PIVOT_MODES} onChange={(v) => onChange({ ...relation, pivot: { mode: v } })} errors={errors('pivot.mode')} />
                {pivot.mode === 'generate' && (
                    <>
                        <Text label="Nom de la table" value={pivot.table} placeholder="convention alphabétique" onChange={(v) => setPivot('table', v)} errors={errors('pivot.table')} />
                        <div className="flex items-end">
                            <Toggle label="Timestamps" checked={pivot.timestamps} onChange={(v) => setPivot('timestamps', v)} errors={errors('pivot.timestamps')} />
                        </div>
                    </>
                )}
                {pivot.mode === 'module' && (
                    <Choice
                        label="Module pivot"
                        value={pivot.module}
                        empty="Choisir…"
                        options={pivotModules.map((model) => ({ value: model.slug, label: `${model.slug} (${model.model})` }))}
                        onChange={(v) => setPivot('module', v)}
                        errors={errors('pivot.module')}
                    />
                )}
            </div>
            {pivot.mode === 'generate' && (
                <div className="space-y-2">
                    {fields.map((field, index) => (
                        <FieldEditor
                            key={index}
                            pivot
                            field={field}
                            types={types}
                            errors={(path) => errors(`pivot.fields.${index}.${path}`)}
                            onChange={(next) => setPivot('fields', fields.map((item, i) => (i === index ? next : item)))}
                            onRemove={() => setPivot('fields', fields.filter((_, i) => i !== index))}
                        />
                    ))}
                    <Button
                        variant="secondary"
                        onClick={() => onChange({ ...relation, in_form: false, pivot: { ...pivot, fields: [...fields, newField('string', types, true)] } })}
                    >
                        <Plus className="h-4 w-4" /> Champ pivot
                    </Button>
                </div>
            )}
        </div>
    );
}

export default function RelationEditor({ relation, types, models, onChange, onRemove, errors }) {
    const set = (key, value) => onChange({ ...relation, [key]: value });
    const pivot = relation.pivot ?? { mode: 'none' };
    const hasPivotFields = (pivot.fields ?? []).length > 0;

    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
            <div className="grid gap-3 sm:grid-cols-4">
                <Choice label="Type" value={relation.type} options={TYPES} onChange={(v) => onChange({ type: v, target: relation.target })} errors={errors('type')} />
                <Text label="Modèle cible" value={relation.target} list="known-models" placeholder="Client" onChange={(v) => set('target', v)} errors={errors('target')} />
                <Text label="Nom de la méthode" value={relation.name} placeholder="dérivé de la cible" onChange={(v) => set('name', v)} errors={errors('name')} />
                <div className="flex items-end justify-end">
                    <Button variant="ghost" onClick={onRemove} aria-label="Retirer la relation">
                        <Trash2 className="h-4 w-4 text-red-600" />
                    </Button>
                </div>
            </div>

            <div className="mt-3 grid gap-3 sm:grid-cols-4">
                <Text label="Libellé" value={relation.label} placeholder="dérivé du nom" onChange={(v) => set('label', v)} errors={errors('label')} />
                {['belongsTo', 'belongsToMany'].includes(relation.type) && (
                    <Text label="Colonne affichée" value={relation.display} placeholder="name" onChange={(v) => set('display', v)} errors={errors('display')} />
                )}
                {relation.type === 'belongsTo' && (
                    <Choice
                        label="À la suppression de la cible"
                        value={relation.on_delete ?? (relation.nullable ? 'null' : 'restrict')}
                        options={ON_DELETE}
                        onChange={(v) => set('on_delete', v)}
                        errors={errors('on_delete')}
                    />
                )}
                {relation.type !== 'belongsToMany' && (
                    <Text label="Clé étrangère" value={relation.foreign_key} placeholder="dérivée" onChange={(v) => set('foreign_key', v)} errors={errors('foreign_key')} />
                )}
            </div>

            <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                {relation.type === 'belongsTo' && (
                    <>
                        <Toggle label="Facultative" checked={relation.nullable} onChange={(v) => set('nullable', v)} errors={errors('nullable')} />
                        <Toggle label="Tableau" checked={relation.in_table ?? true} onChange={(v) => set('in_table', v)} errors={errors('in_table')} />
                        <Toggle label="Formulaire (select)" checked={relation.in_form ?? true} onChange={(v) => set('in_form', v)} errors={errors('in_form')} />
                    </>
                )}
                {relation.type === 'belongsToMany' && (
                    <Toggle
                        label="Formulaire (sélection multiple)"
                        checked={relation.in_form}
                        disabled={hasPivotFields}
                        onChange={(v) => set('in_form', v)}
                        errors={errors('in_form')}
                    />
                )}
            </div>

            {relation.type === 'belongsToMany' && (
                <PivotOptions relation={relation} pivot={pivot} types={types} models={models} onChange={onChange} errors={errors} />
            )}
        </div>
    );
}
