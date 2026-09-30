import { useForm } from '@inertiajs/react';
import { Button, Input, Select } from '../../../components/admin';

export default function ClientTagForm({ record = null, options }) {
    const { data, setData, post, put, processing, errors } = useForm({
        client_id: record?.client_id ?? '',
        tag_id: record?.tag_id ?? '',
        note: record?.note ?? '',
    });

    const bind = (field) => ({
        value: data[field] ?? '',
        onChange: (event) => setData(field, event.target.value),
        error: errors[field],
    });

    const submit = (event) => {
        event.preventDefault();

        if (record) {
            put(route('admin.client-tag.update', record.id));
        } else {
            post(route('admin.client-tag.store'));
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Select label={'Client'} required options={options.client_id ?? []} {...bind('client_id')} />
            <Select label={'Tag'} required options={options.tag_id ?? []} {...bind('tag_id')} />
            <Input label={'Note'} maxLength={255} {...bind('note')} />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.client-tag.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
