import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import {
    AdminLayout,
    Button,
    ConfirmDialog,
    DataTable,
    Filters,
    Pagination,
    formatNumber,
    optionLabel,
} from '../../components/admin';

const TITLE = 'Categories';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const indent = (row) => (filters.sort === '_lft' && !filters.search ? `${(row.depth ?? 0) * 1.5}rem` : 0);

    const columns = [
        {
            key: 'owner_type',
            label: 'Type',
            render: (row) => optionLabel(options.owner_type, row.owner_type),
        },
        {
            key: 'owner_id',
            label: 'Élément',
            render: (row) => row.owner?.[options.owner_display?.[row.owner_type]] ?? row.owner_id ?? '—',
        },
        {
            key: 'name',
            label: 'Name',
            sortable: true,
            render: (row) => <span style={{ paddingLeft: indent(row) }}>{row.name ?? '—'}</span>,
        },
        {
            key: 'position',
            label: 'Position',
            sortable: true,
            render: (row) => formatNumber(row.position),
        },
        {
            key: 'cover',
            label: 'Cover',
            render: (row) => row.cover_url ? <img src={row.cover_url} alt="" className="h-10 w-10 rounded object-cover" /> : '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.categories.destroy', toDelete.id), {
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
                <Button href={route('admin.categories.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={true}
                filters={[
                    { name: 'owner_type', label: 'Type', options: options.owner_type ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={'id'}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" href={route('admin.categories.show', row.id)} aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" href={route('admin.categories.edit', row.id)} aria-label="Modifier">
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
