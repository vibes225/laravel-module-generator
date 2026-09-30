import { Plus, Trash2 } from 'lucide-react';
import { Button, Choice, Errors, Text, Toggle } from './form';

const KEY_TYPES = [
    { value: 'int', label: 'Entier' },
    { value: 'uuid', label: 'UUID' },
    { value: 'ulid', label: 'ULID' },
];

const PIVOT_ON_DELETE = ['cascade', 'restrict', 'no_action'].map((value) => ({ value, label: value }));

export function MorphEditor({ morph, onChange, errors, forPivot = false }) {
    const set = (key, value) => onChange({ ...morph, [key]: value });
    const types = morph.types ?? [];
    const setType = (index, key, value) => set('types', types.map((item, i) => (i === index ? { ...item, [key]: value } : item)));

    return (
        <div className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-4">
                <Text label="Nom de la relation" value={morph.name} placeholder="commentable" onChange={(v) => set('name', v)} errors={errors('morph.name')} />
                <Choice label="Type de clé" value={morph.key_type ?? 'int'} options={KEY_TYPES} onChange={(v) => set('key_type', v)} errors={errors('morph.key_type')} />
                {!forPivot && (
                    <div className="flex items-end">
                        <Toggle label="Rattachement facultatif" checked={morph.nullable} onChange={(v) => set('nullable', v)} errors={errors('morph.nullable')} />
                    </div>
                )}
            </div>
            <div className="space-y-2">
                <p className="text-xs text-slate-500">
                    Types autorisés (liste vide : tout modèle, colonnes hors formulaires). Aucune clé étrangère n&apos;est possible en base.
                </p>
                {types.map((type, index) => (
                    <div key={index} className="grid gap-3 sm:grid-cols-4">
                        <Text label="Modèle" value={type.model} list="known-models" onChange={(v) => setType(index, 'model', v)} errors={errors(`morph.types.${index}.model`)} />
                        <Text label="Libellé" value={type.label} placeholder="dérivé" onChange={(v) => setType(index, 'label', v)} errors={errors(`morph.types.${index}.label`)} />
                        <Text label="Colonne affichée" value={type.display_column} placeholder="name" onChange={(v) => setType(index, 'display_column', v)} errors={errors(`morph.types.${index}.display_column`)} />
                        <div className="flex items-end justify-end">
                            <Button variant="ghost" onClick={() => set('types', types.filter((_, i) => i !== index))} aria-label="Retirer le type">
                                <Trash2 className="h-4 w-4 text-red-600" />
                            </Button>
                        </div>
                    </div>
                ))}
                <Button variant="secondary" onClick={() => set('types', [...types, { model: '' }])}>
                    <Plus className="h-4 w-4" /> Type autorisé
                </Button>
            </div>
        </div>
    );
}

function Side({ title, side, onChange, errors, prefix }) {
    const set = (key, value) => onChange({ ...side, [key]: value });

    return (
        <div className="space-y-3 rounded-md border border-slate-200 p-3">
            <div className="flex items-center justify-between">
                <h3 className="text-xs font-semibold text-slate-700 uppercase">{title}</h3>
                <Toggle label="Polymorphe" checked={side.polymorphic} onChange={(v) => onChange({ polymorphic: v })} />
            </div>
            {!side.polymorphic && (
                <div className="grid gap-3 sm:grid-cols-2">
                    <Text label="Modèle" value={side.model} list="known-models" onChange={(v) => set('model', v)} errors={errors(`${prefix}.model`)} />
                    <Text label="Clé étrangère" value={side.foreign_key} placeholder="dérivée" onChange={(v) => set('foreign_key', v)} errors={errors(`${prefix}.foreign_key`)} />
                    <Choice label="À la suppression" value={side.on_delete ?? 'cascade'} options={PIVOT_ON_DELETE} onChange={(v) => set('on_delete', v)} errors={errors(`${prefix}.on_delete`)} />
                    <Text label="Colonne affichée" value={side.display} placeholder="name" onChange={(v) => set('display', v)} errors={errors(`${prefix}.display`)} />
                </div>
            )}
        </div>
    );
}

export function PivotEditor({ pivot, onChange, errors }) {
    const set = (key, value) => onChange({ ...pivot, [key]: value });

    return (
        <div className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
                <Side title="Côté gauche" side={pivot.left ?? {}} prefix="pivot.left" errors={errors} onChange={(v) => set('left', v)} />
                <Side title="Côté droit" side={pivot.right ?? {}} prefix="pivot.right" errors={errors} onChange={(v) => set('right', v)} />
            </div>
            <div className="flex flex-wrap gap-x-5 gap-y-2">
                <Toggle label="Couple unique" checked={pivot.unique_pair ?? true} onChange={(v) => set('unique_pair', v)} errors={errors('pivot.unique_pair')} />
                <Toggle label="Clé primaire id" checked={pivot.primary_id ?? true} onChange={(v) => set('primary_id', v)} errors={errors('pivot.primary_id')} />
            </div>
            <Errors messages={errors('pivot')} />
        </div>
    );
}
