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
    optionLabel,
} from '../../components/admin';

const TITLE = 'Taggables';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const columns = [
        {
            key: 'tag_id',
            label: 'Tag',
            render: (row) => row.tag?.name ?? '—',
        },
        {
            key: 'taggable_type',
            label: 'Type',
            render: (row) => optionLabel(options.taggable_type, row.taggable_type),
        },
        {
            key: 'taggable_id',
            label: 'Élément',
            render: (row) => row.taggable?.[options.taggable_display?.[row.taggable_type]] ?? row.taggable_id ?? '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.taggables.destroy', toDelete.id), {
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
                <Button href={route('admin.taggables.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={false}
                filters={[
                    { name: 'tag_id', label: 'Tag', options: options.tag_id ?? [] },
                    { name: 'taggable_type', label: 'Type', options: options.taggable_type ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={'id'}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" href={route('admin.taggables.show', row.id)} aria-label="Voir">
                            <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" href={route('admin.taggables.edit', row.id)} aria-label="Modifier">
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
