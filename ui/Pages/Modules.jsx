import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { Button, Layout, Toggle } from '../components/form';
import { api, url } from '../lib/api';

const STATES = {
    unchanged: ['inchangé', 'text-green-700 bg-green-50'],
    modified: ['modifié', 'text-amber-800 bg-amber-50'],
    missing: ['absent (ignoré)', 'text-slate-500 bg-slate-100'],
    unknown: ['génération interrompue', 'text-amber-800 bg-amber-50'],
};

function RemovalDialog({ removal, onCancel, onConfirm, busy }) {
    const [includeModified, setIncludeModified] = useState(false);
    const hasModified = removal.files.some((file) => file.state === 'modified');

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div className="w-full max-w-2xl rounded-xl bg-white shadow-xl">
                <div className="border-b border-slate-100 px-5 py-4">
                    <h2 className="text-base font-semibold text-slate-900">Supprimer le module « {removal.module} »</h2>
                    <p className="mt-1 text-xs text-slate-500">
                        Seuls les fichiers du manifest sont supprimés. La table reste en base : annuler la migration
                        relève du développeur.
                    </p>
                </div>
                <ul className="max-h-80 divide-y divide-slate-100 overflow-y-auto px-5">
                    {removal.files.map((file) => (
                        <li key={file.path} className="flex items-center justify-between gap-3 py-2 text-sm">
                            <span className="truncate font-mono text-xs text-slate-700">{file.path}</span>
                            <span className={`shrink-0 rounded px-2 py-0.5 text-xs ${STATES[file.state][1]}`}>
                                {STATES[file.state][0]}
                            </span>
                        </li>
                    ))}
                </ul>
                <div className="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-3">
                    <div>
                        {hasModified && (
                            <Toggle
                                label="Supprimer aussi les fichiers modifiés"
                                checked={includeModified}
                                onChange={setIncludeModified}
                            />
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={onCancel} disabled={busy}>
                            Annuler
                        </Button>
                        <Button variant="danger" onClick={() => onConfirm(includeModified)} disabled={busy}>
                            Supprimer
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function Modules({ modules, archived }) {
    const { tool } = usePage().props;
    const [removal, setRemoval] = useState(null);
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState(null);

    const askRemoval = async (slug) => {
        const { ok, data } = await api('GET', url(tool.prefix, `api/modules/${slug}/removal`));

        if (ok) {
            setRemoval(data.plan);
        } else {
            setMessage(data.message ?? 'Module introuvable.');
        }
    };

    const confirmRemoval = async (includeModified) => {
        setBusy(true);
        const { ok, data } = await api('DELETE', url(tool.prefix, `api/modules/${removal.module}`), {
            include_modified: includeModified,
        });
        setBusy(false);
        setRemoval(null);

        if (ok) {
            const kept = data.result.kept.length ? `, ${data.result.kept.length} conservé(s) (modifiés)` : '';
            setMessage(`${data.result.deleted.length} fichier(s) supprimé(s)${kept}.`);
            router.reload();
        } else {
            setMessage(data.message ?? 'Suppression impossible.');
        }
    };

    const newModule = (
        <Link
            href={url(tool.prefix, 'create')}
            className="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
        >
            <Plus className="h-4 w-4" /> Nouveau module
        </Link>
    );

    return (
        <Layout title="Modules" actions={newModule}>
            <Head title="Modules" />
            {message && (
                <div className="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">{message}</div>
            )}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th className="px-4 py-3 font-medium">Module</th>
                            <th className="px-4 py-3 font-medium">Modèle</th>
                            <th className="px-4 py-3 font-medium">Nature</th>
                            <th className="px-4 py-3 font-medium">Statut</th>
                            <th className="px-4 py-3 font-medium">Fichiers</th>
                            <th className="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {modules.map((module) => (
                            <tr key={module.slug}>
                                <td className="px-4 py-3">
                                    <div className="font-medium text-slate-900">{module.name}</div>
                                    <div className="font-mono text-xs text-slate-500">{module.slug}</div>
                                </td>
                                <td className="px-4 py-3 font-mono text-xs">{module.model}</td>
                                <td className="px-4 py-3">{module.kind}</td>
                                <td className="px-4 py-3">
                                    <span
                                        className={`rounded px-2 py-0.5 text-xs ${module.complete ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-800'}`}
                                    >
                                        {module.complete ? 'complet' : 'pending'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">{module.files}</td>
                                <td className="px-4 py-3 text-right">
                                    <Button variant="ghost" onClick={() => askRemoval(module.slug)} aria-label="Supprimer">
                                        <Trash2 className="h-4 w-4 text-red-600" />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {modules.length === 0 && <p className="px-4 py-10 text-center text-sm text-slate-500">Aucun module généré.</p>}
            </div>
            {archived.length > 0 && (
                <p className="text-xs text-slate-500">
                    Définitions archivées (régénérer avec <code>module-generator:make --from=…</code>) : {archived.join(', ')}
                </p>
            )}
            {removal && (
                <RemovalDialog removal={removal} busy={busy} onCancel={() => setRemoval(null)} onConfirm={confirmRemoval} />
            )}
        </Layout>
    );
}
