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
    optionLabel,
} from '../../components/admin';

const TITLE = 'Clients';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const columns = [
        {
            key: 'name',
            label: 'Name',
            sortable: true,
            render: (row) => row.name ?? '—',
        },
        {
            key: 'email',
            label: 'Email',
            sortable: true,
            render: (row) => row.email ?? '—',
        },
        {
            key: 'vip',
            label: 'Vip',
            render: (row) => <Badge tone={row.vip ? 'green' : 'slate'}>{formatBoolean(row.vip)}</Badge>,
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            render: (row) => row.status ? <Badge>{optionLabel(options.status, row.status)}</Badge> : '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.clients.destroy', toDelete.id), {
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
                <Button href={route('admin.clients.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={true}
                filters={[
                    { name: 'vip', label: 'Vip', options: options.vip ?? [] },
                    { name: 'status', label: 'Status', options: options.status ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={'id'}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" href={route('admin.clients.show', row.id)} aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" href={route('admin.clients.edit', row.id)} aria-label="Modifier">
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
