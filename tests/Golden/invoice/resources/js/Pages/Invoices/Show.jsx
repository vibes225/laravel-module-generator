import { Pencil } from 'lucide-react';
import { AdminLayout, Badge, Button, formatBoolean, formatDate, formatDateTime, formatNumber, optionLabel } from '../../components/admin';

const TITLE = 'Invoices';

export default function Show({ record, options }) {
    return (
        <AdminLayout
            title={record.number ?? TITLE}
            breadcrumbs={[{ label: TITLE, href: route('admin.invoices.index') }, { label: 'Fiche' }]}
            actions={
                <Button href={route('admin.invoices.edit', record.id)}>
                    <Pencil className="h-4 w-4" />
                    Modifier
                </Button>
            }
        >
            <div className="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Number</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.number ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Status</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.status ? <Badge>{optionLabel(options.status, record.status)}</Badge> : '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Amount</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{formatNumber(record.amount, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Issued at</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{formatDate(record.issued_at)}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Attachment</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.attachment_url ? <a href={record.attachment_url} target="_blank" rel="noreferrer" className="text-indigo-600 hover:underline">Télécharger</a> : '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Is paid</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{<Badge tone={record.is_paid ? 'green' : 'slate'}>{formatBoolean(record.is_paid)}</Badge>}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Meta</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.meta == null ? '—' : <code className="text-xs">{JSON.stringify(record.meta)}</code>}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Notes</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.notes ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Client</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.client?.name ?? '—'}</dd>
                    </div>
                    <div className="grid gap-1 px-6 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt className="text-sm font-medium text-slate-500">Tags</dt>
                        <dd className="text-sm text-slate-900 sm:col-span-2">{record.tags?.map((item) => item.name).join(', ') || '—'}</dd>
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
