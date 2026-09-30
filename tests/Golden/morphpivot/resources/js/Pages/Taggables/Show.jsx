import { Pencil } from 'lucide-react';
import { AdminLayout, Button, formatDateTime, optionLabel } from '../../components/admin';

const TITLE = 'Taggables';

export default function Show({ record, options }) {
    return (
        <AdminLayout
            title={`${TITLE} #${record.id}`}
            breadcrumbs={[{ label: TITLE, href: route('admin.taggables.index') }, { label: 'Fiche' }]}
            actions={
                <Button href={route('admin.taggables.edit', record.id)}>
                    <Pencil className="h-4 w-4" />
                    Modifier
                </Button>
            }
        >
            <div className="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Tag</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.tag?.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Type</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{optionLabel(options.taggable_type, record.taggable_type)}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Élément</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.taggable?.[options.taggable_display?.[record.taggable_type]] ?? record.taggable_id ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Créé le</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{formatDateTime(record.created_at)}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Modifié le</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{formatDateTime(record.updated_at)}</dd>
                    </div>
                </dl>
            </div>
        </AdminLayout>
    );
}
