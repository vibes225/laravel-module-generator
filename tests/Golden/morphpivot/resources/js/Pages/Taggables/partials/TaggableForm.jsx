import { useForm } from '@inertiajs/react';
import { Button, Select } from '../../../components/admin';

export default function TaggableForm({ record = null, options }) {
    const { data, setData, post, put, processing, errors } = useForm({
        tag_id: record?.tag_id ?? '',
        taggable_type: record?.taggable_type ?? '',
        taggable_id: record?.taggable_id ?? '',
    });

    const bind = (field) => ({
        value: data[field] ?? '',
        onChange: (event) => setData(field, event.target.value),
        error: errors[field],
    });

    const submit = (event) => {
        event.preventDefault();

        if (record) {
            put(route('admin.taggables.update', record.id));
        } else {
            post(route('admin.taggables.store'));
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Select label={'Tag'} required options={options.tag_id ?? []} {...bind('tag_id')} />
            <Select
                label="Type" required
                options={options.taggable_type ?? []}
                value={data.taggable_type ?? ''}
                onChange={(event) => setData((values) => ({ ...values, taggable_type: event.target.value, taggable_id: '' }))}
                error={errors.taggable_type}
            />
            <Select
                label="Élément" required
                options={options.taggable_id?.[data.taggable_type] ?? []}
                disabled={!data.taggable_type}
                {...bind('taggable_id')}
            />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.taggables.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
