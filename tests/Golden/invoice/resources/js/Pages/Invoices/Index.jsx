import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import {
    AdminLayout,
    Badge,
    Button,
    ConfirmDialog,
    DataTable,
    Filters,
    Pagination,
    formatBoolean,
    formatDate,
    formatNumber,
    optionLabel,
} from '../../components/admin';

const TITLE = 'Invoices';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const columns = [
        {
            key: 'number',
            label: 'Number',
            sortable: true,
            render: (row) => row.number ?? '—',
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            render: (row) => row.status ? <Badge>{optionLabel(options.status, row.status)}</Badge> : '—',
        },
        {
            key: 'amount',
            label: 'Amount',
            sortable: true,
            render: (row) => formatNumber(row.amount, { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
        },
        {
            key: 'issued_at',
            label: 'Issued at',
            sortable: true,
            render: (row) => formatDate(row.issued_at),
        },
        {
            key: 'is_paid',
            label: 'Is paid',
            render: (row) => <Badge tone={row.is_paid ? 'green' : 'slate'}>{formatBoolean(row.is_paid)}</Badge>,
        },
        {
            key: 'client_id',
            label: 'Client',
            render: (row) => row.client?.name ?? '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.invoices.destroy', toDelete.id), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setToDelete(null);
            },
        });
    };

    return (
        <AdminLayout
            title={TITLE}
            breadcrumbs={[{ label: TITLE }]}
            actions={
                <Button href={route('admin.invoices.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={true}
                filters={[
                    { name: 'status', label: 'Status', options: options.status ?? [] },
                    { name: 'is_paid', label: 'Is paid', options: options.is_paid ?? [] },
                    { name: 'client_id', label: 'Client', options: options.client_id ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={'id'}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" href={route('admin.invoices.show', row.id)} aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" href={route('admin.invoices.edit', row.id)} aria-label="Modifier">
                            <Pencil className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setToDelete(row)} aria-label="Supprimer">
                            <Trash2 className="h-4 w-4 text-red-600" />
                        </Button>
                    </div>
                )}
            />
            <Pagination paginator={records} />
            <ConfirmDialog
                open={toDelete !== null}
                title="Supprimer"
                message="Voulez-vous vraiment supprimer cet enregistrement ? Cette action est irréversible."
                confirmLabel="Supprimer"
                processing={processing}
                onCancel={() => setToDelete(null)}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
