import { useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Eye, Plus, Wand2 } from 'lucide-react';
import FieldEditor, { newField } from '../components/FieldEditor';
import PlanPreview from '../components/PlanPreview';
import RelationEditor, { newRelation } from '../components/RelationEditor';
import { MorphEditor, PivotEditor } from '../components/StructureEditors';
import { Button, Choice, Errors, Layout, NumberInput, Section, Text, Toggle } from '../components/form';
import { api, clean, url } from '../lib/api';

const KINDS = [
    { value: 'standard', label: 'Standard' },
    { value: 'pivot', label: 'Pivot (relie deux modules)' },
];

export default function Builder({ types: typeList, models, icons }) {
    const { tool } = usePage().props;
    const types = useMemo(() => Object.fromEntries(typeList.map((type) => [type.name, type])), [typeList]);
    const [definition, setDefinition] = useState({
        kind: 'standard',
        singular: '',
        icon: 'folder',
        fields: [{ ...newField('string', types), name: 'name' }],
        relations: [],
        options: { timestamps: true, soft_deletes: false },
        menu: { enabled: true },
    });
    const [errors, setErrors] = useState({});
    const [plan, setPlan] = useState(null);
    const [previewed, setPreviewed] = useState(null);
    const [derived, setDerived] = useState(null);
    const [busy, setBusy] = useState(false);
    const [result, setResult] = useState(null);

    const set = (key, value) => setDefinition((current) => ({ ...current, [key]: value }));
    const setIn = (group, key, value) => setDefinition((current) => ({ ...current, [group]: { ...current[group], [key]: value } }));
    const at = (prefix) => (path) => errors[prefix ? `${prefix}.${path}` : path];
    const payload = () => {
        const sides = definition.kind === 'pivot' ? [definition.pivot?.left, definition.pivot?.right] : [];
        const morphed = definition.morph?.enabled || sides.some((side) => side?.polymorphic);

        return clean({
            ...definition,
            pivot: definition.kind === 'pivot' ? definition.pivot : undefined,
            tree: definition.kind === 'pivot' ? false : definition.tree,
            morph: morphed ? { ...definition.morph, enabled: undefined } : undefined,
        });
    };
    const current = JSON.stringify(payload());

    const preview = async () => {
        setBusy(true);
        setResult(null);
        const { status, data } = await api('POST', url(tool.prefix, 'api/preview'), { definition: payload() });
        setBusy(false);
        setDerived(data.definition ?? null);

        if (status === 422) {
            setErrors(data.errors);
            setPlan(null);
        } else {
            setErrors({});
            setPlan(data.plan);
            setPreviewed(current);
        }
    };

    const generate = async () => {
        setBusy(true);
        const { status, data } = await api('POST', url(tool.prefix, 'api/generate'), { definition: payload() });
        setBusy(false);

        if (status === 201) {
            setResult({ ok: true, files: data.manifest.files.length, slug: data.manifest.module.slug, warnings: data.warnings });
            setPlan(null);
        } else if (status === 422) {
            setErrors(data.errors);
        } else {
            setResult({ ok: false, message: data.message ?? 'Génération impossible : conflit détecté, relancer la prévisualisation.' });
            if (data.plan) {
                setPlan(data.plan);
            }
        }
    };

    const updateList = (key, index, value) => set(key, definition[key].map((item, i) => (i === index ? value : item)));
    const removeFrom = (key, index) => set(key, definition[key].filter((_, i) => i !== index));
    const pivot = definition.kind === 'pivot';
    const polymorphicSide = pivot && (definition.pivot?.left?.polymorphic || definition.pivot?.right?.polymorphic);
    const allErrors = Object.entries(errors);

    return (
        <Layout
            title="Nouveau module"
            actions={
                <Link href={url(tool.prefix, '')} className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                    <ArrowLeft className="h-4 w-4" /> Modules
                </Link>
            }
        >
            <Head title="Nouveau module" />
            <datalist id="known-models">
                {models.map((model) => (
                    <option key={model.model} value={model.model} />
                ))}
            </datalist>

            <Section title="Identité" description="Seul le libellé singulier est requis : le reste est dérivé (et modifiable).">
                <div className="grid gap-3 sm:grid-cols-4">
                    <Choice label="Nature" value={definition.kind} options={KINDS} onChange={(v) => set('kind', v)} errors={at()('kind')} />
                    <Text label="Libellé singulier" value={definition.singular} placeholder="Client" onChange={(v) => set('singular', v)} errors={at()('singular')} />
                    <Text label="Libellé pluriel" value={definition.name} placeholder={derived?.name ?? 'dérivé'} onChange={(v) => set('name', v)} errors={at()('name')} />
                    <Choice label="Icône" value={definition.icon} options={icons.map((icon) => ({ value: icon, label: icon }))} onChange={(v) => set('icon', v)} errors={at()('icon')} />
                    <Text label="Modèle" value={definition.model} placeholder={derived?.model ?? 'dérivé'} onChange={(v) => set('model', v)} errors={at()('model')} />
                    <Text label="Table" value={definition.table} placeholder={derived?.table ?? 'dérivée'} onChange={(v) => set('table', v)} errors={at()('table')} />
                    <Text label="Slug" value={definition.slug} placeholder={derived?.slug ?? 'dérivé'} onChange={(v) => set('slug', v)} errors={at()('slug')} />
                </div>
            </Section>

            {pivot && (
                <Section title="Module pivot" description="Deux clés étrangères, unicité du couple dans les requests, deux selects et deux filtres.">
                    <PivotEditor pivot={definition.pivot ?? {}} onChange={(v) => set('pivot', v)} errors={at()} />
                </Section>
            )}

            <Section
                title={pivot ? 'Champs supplémentaires' : 'Champs'}
                actions={
                    <Button variant="secondary" onClick={() => set('fields', [...definition.fields, newField('string', types)])}>
                        <Plus className="h-4 w-4" /> Champ
                    </Button>
                }
            >
                {definition.fields.map((field, index) => (
                    <FieldEditor key={index} field={field} types={types} errors={at(`fields.${index}`)} onChange={(v) => updateList('fields', index, v)} onRemove={() => removeFrom('fields', index)} />
                ))}
                {definition.fields.length === 0 && <p className="text-sm text-slate-500">Aucun champ.</p>}
                <Errors messages={at()('fields')} />
            </Section>

            <Section
                title="Relations"
                description="Déclaratives : seul ce qui appartient au module est généré (méthode, clé étrangère, select)."
                actions={
                    <Button variant="secondary" onClick={() => set('relations', [...definition.relations, newRelation()])}>
                        <Plus className="h-4 w-4" /> Relation
                    </Button>
                }
            >
                {definition.relations.map((relation, index) => (
                    <RelationEditor key={index} relation={relation} types={types} models={models} errors={at(`relations.${index}`)} onChange={(v) => updateList('relations', index, v)} onRemove={() => removeFrom('relations', index)} />
                ))}
                {definition.relations.length === 0 && <p className="text-sm text-slate-500">Aucune relation.</p>}
            </Section>

            <Section title="Structure et options">
                <div className="flex flex-wrap gap-x-6 gap-y-2">
                    {!pivot && <Toggle label="Structure en arbre (kalnoy/nestedset)" checked={definition.tree} onChange={(v) => set('tree', v)} errors={at()('tree')} />}
                    {!pivot && <Toggle label="Relation polymorphe" checked={definition.morph?.enabled} onChange={(v) => set('morph', { ...definition.morph, enabled: v })} />}
                    <Toggle label="Timestamps" checked={definition.options.timestamps} onChange={(v) => setIn('options', 'timestamps', v)} errors={at()('options.timestamps')} />
                    <Toggle label="Suppression logique" checked={definition.options.soft_deletes} onChange={(v) => setIn('options', 'soft_deletes', v)} errors={at()('options.soft_deletes')} />
                    <Toggle label="Entrée de menu" checked={definition.menu.enabled} onChange={(v) => setIn('menu', 'enabled', v)} errors={at()('menu.enabled')} />
                </div>
                {(definition.morph?.enabled || polymorphicSide) && (
                    <MorphEditor morph={definition.morph ?? {}} forPivot={pivot} onChange={(v) => set('morph', { ...v, enabled: true })} errors={at()} />
                )}
                <div className="grid gap-3 sm:grid-cols-4">
                    <NumberInput label="Éléments par page" value={definition.options.per_page} placeholder={15} onChange={(v) => setIn('options', 'per_page', v)} errors={at()('options.per_page')} />
                    <Text label="Groupe du menu" value={definition.menu.group} placeholder="main" onChange={(v) => setIn('menu', 'group', v)} errors={at()('menu.group')} />
                    <NumberInput label="Ordre dans le menu" value={definition.menu.order} placeholder={100} onChange={(v) => setIn('menu', 'order', v)} errors={at()('menu.order')} />
                    <Text label="Permission (Gate)" value={definition.menu.permission} placeholder="aucune" onChange={(v) => setIn('menu', 'permission', v)} errors={at()('menu.permission')} />
                </div>
            </Section>

            <div className="sticky bottom-0 z-10 -mx-6 flex items-center justify-end gap-2 border-t border-slate-200 bg-white/90 px-6 py-3 backdrop-blur">
                {plan && previewed !== current && <span className="mr-auto text-xs text-amber-700">La définition a changé : prévisualiser à nouveau.</span>}
                <Button variant="secondary" onClick={preview} disabled={busy}>
                    <Eye className="h-4 w-4" /> Prévisualiser
                </Button>
                <Button onClick={generate} disabled={busy || !plan || !plan.executable || previewed !== current}>
                    <Wand2 className="h-4 w-4" /> Générer
                </Button>
            </div>

            {allErrors.length > 0 && (
                <Section title="Définition invalide" description="Chaque erreur est aussi affichée près du champ concerné.">
                    <ul className="space-y-1 text-sm">
                        {allErrors.map(([path, messages]) => (
                            <li key={path} className="text-red-700">
                                <code className="font-mono text-xs">{path}</code> : {messages.join(' ')}
                            </li>
                        ))}
                    </ul>
                </Section>
            )}

            {result && (
                <div className={`rounded-lg border px-4 py-3 text-sm ${result.ok ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'}`}>
                    {result.ok ? (
                        <>
                            Module « {result.slug} » généré : {result.files} fichier(s). Lancer <code>php artisan migrate</code> puis{' '}
                            <code>npm run build</code>.{' '}
                            <Link href={url(tool.prefix, '')} className="font-medium underline">
                                Retour aux modules
                            </Link>
                            {result.warnings.map((warning) => (
                                <p key={warning} className="mt-1 text-amber-800">
                                    {warning}
                                </p>
                            ))}
                        </>
                    ) : (
                        result.message
                    )}
                </div>
            )}

            {plan && (
                <Section title="Prévisualisation" description="Plan calculé par le moteur, identique à la commande module-generator:make --dry-run.">
                    <PlanPreview plan={plan} />
                </Section>
            )}
        </Layout>
    );
}
