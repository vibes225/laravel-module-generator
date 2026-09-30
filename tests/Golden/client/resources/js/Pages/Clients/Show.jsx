import { Pencil } from 'lucide-react';
import { AdminLayout, Badge, Button, formatBoolean, formatDateTime, optionLabel } from '../../components/admin';

const TITLE = 'Clients';

export default function Show({ record, options }) {
    return (
        <AdminLayout
            title={record.name ?? TITLE}
            breadcrumbs={[{ label: TITLE, href: route('admin.clients.index') }, { label: 'Fiche' }]}
            actions={
                <Button href={route('admin.clients.edit', record.id)}>
                    <Pencil className="h-4 w-4" />
                    Modifier
                </Button>
            }
        >
            <div className="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Name</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Email</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.email ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Vip</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{<Badge tone={record.vip ? 'green' : 'slate'}>{formatBoolean(record.vip)}</Badge>}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Status</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.status ? <Badge>{optionLabel(options.status, record.status)}</Badge> : '—'}</dd>
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
