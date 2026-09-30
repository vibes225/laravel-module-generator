import { Pencil } from 'lucide-react';
import { AdminLayout, Button, formatDateTime, formatNumber, optionLabel } from '../../components/admin';

const TITLE = 'Categories';

export default function Show({ record, options }) {
    return (
        <AdminLayout
            title={record.name ?? TITLE}
            breadcrumbs={[{ label: TITLE, href: route('admin.categories.index') }, { label: 'Fiche' }]}
            actions={
                <Button href={route('admin.categories.edit', record.id)}>
                    <Pencil className="h-4 w-4" />
                    Modifier
                </Button>
            }
        >
            <div className="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Type</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{optionLabel(options.owner_type, record.owner_type)}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Élément</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.owner?.[options.owner_display?.[record.owner_type]] ?? record.owner_id ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Parent</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.parent?.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Name</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Position</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{formatNumber(record.position)}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Cover</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.cover_url ? <img src={record.cover_url} alt="" className="h-10 w-10 rounded object-cover" /> : '—'}</dd>
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
