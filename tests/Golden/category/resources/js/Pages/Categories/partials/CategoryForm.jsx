import { useForm } from '@inertiajs/react';
import { Button, Input, Select } from '../../../components/admin';

export default function CategoryForm({ record = null, options }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        owner_type: record?.owner_type ?? '',
        owner_id: record?.owner_id ?? '',
        parent_id: record?.parent_id ?? '',
        name: record?.name ?? '',
        position: record?.position ?? '',
        cover: null,
    });

    const bind = (field) => ({
        value: data[field] ?? '',
        onChange: (event) => setData(field, event.target.value),
        error: errors[field],
    });

    const submit = (event) => {
        event.preventDefault();

        if (record) {
            transform((values) => ({ ...values, _method: 'put' }));
            post(route('admin.categories.update', record.id), { forceFormData: true });
        } else {
            post(route('admin.categories.store'), { forceFormData: true });
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Select
                label="Type"
                options={options.owner_type ?? []}
                value={data.owner_type ?? ''}
                onChange={(event) => setData((values) => ({ ...values, owner_type: event.target.value, owner_id: '' }))}
                error={errors.owner_type}
            />
            <Select
                label="Élément"
                options={options.owner_id?.[data.owner_type] ?? []}
                disabled={!data.owner_type}
                {...bind('owner_id')}
            />
            <Select label={'Parent'} options={options.parent_id ?? []} {...bind('parent_id')} />
            <Input label={'Name'} required maxLength={255} {...bind('name')} />
            <Input label={'Position'} required type="number" step="1" {...bind('position')} />
            <Input
                label={'Cover'}
                type="file" accept=".jpg,.jpeg,.png,.webp"
                onChange={(event) => setData('cover', event.target.files[0] ?? null)}
                hint={record?.cover_url ? 'Laisser vide pour conserver le fichier actuel.' : undefined}
                error={errors.cover}
            />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.categories.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
