import { Pencil } from 'lucide-react';
import { AdminLayout, Button, formatDateTime } from '../../components/admin';

const TITLE = 'Client tags';

export default function Show({ record }) {
    return (
        <AdminLayout
            title={record.note ?? TITLE}
            breadcrumbs={[{ label: TITLE, href: route('admin.client-tag.index') }, { label: 'Fiche' }]}
            actions={
                <Button href={route('admin.client-tag.edit', record.id)}>
                    <Pencil className="h-4 w-4" />
                    Modifier
                </Button>
            }
        >
            <div className="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Client</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.client?.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Tag</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.tag?.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Note</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.note ?? '—'}</dd>
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
