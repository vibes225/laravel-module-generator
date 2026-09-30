import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import {
    AdminLayout,
    Button,
    ConfirmDialog,
    DataTable,
    Filters,
    Pagination,
} from '../../components/admin';

const TITLE = 'Follows';

export default function Index({ records, filters, options }) {
    const [toDelete, setToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const columns = [
        {
            key: 'follower_id',
            label: 'User',
            render: (row) => row.follower?.name ?? '—',
        },
        {
            key: 'followed_id',
            label: 'User',
            render: (row) => row.followed?.name ?? '—',
        },
    ];

    const confirmDelete = () => {
        router.delete(route('admin.user-user.destroy', { follower_id: toDelete.follower_id, followed_id: toDelete.followed_id }), {
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
                <Button href={route('admin.user-user.create')}>
                    <Plus className="h-4 w-4" />
                    Nouveau
                </Button>
            }
        >
            <Filters
                values={filters}
                search={false}
                filters={[
                    { name: 'follower_id', label: 'User', options: options.follower_id ?? [] },
                    { name: 'followed_id', label: 'User', options: options.followed_id ?? [] },
                ]}
            />
            <DataTable
                columns={columns}
                rows={records.data}
                rowKey={(row) => `${row.follower_id}-${row.followed_id}`}
                sort={{ by: filters.sort, direction: filters.direction }}
                actions={(row) => (
                    <div className="flex justify-end gap-1">
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
