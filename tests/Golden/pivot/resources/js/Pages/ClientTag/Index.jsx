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
} from '../../components/admin';

const TITLE = 'Client tags';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const columns = [
        {
            key: 'client_id',
            label: 'Client',
            render: (row) => row.client?.name ?? '—',
        },
        {
            key: 'tag_id',
            label: 'Tag',
            render: (row) => row.tag?.name ?? '—',
        },
        {
            key: 'note',
            label: 'Note',
            sortable: true,
            render: (row) => row.note ?? '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.client-tag.destroy', toDelete.id), {
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
                <Button href={route('admin.client-tag.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={true}
                filters={[
                    { name: 'client_id', label: 'Client', options: options.client_id ?? [] },
                    { name: 'tag_id', label: 'Tag', options: options.tag_id ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={'id'}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" href={route('admin.client-tag.show', row.id)} aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" href={route('admin.client-tag.edit', row.id)} aria-label="Modifier">
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
