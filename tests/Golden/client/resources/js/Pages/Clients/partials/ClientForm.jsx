import { useForm } from '@inertiajs/react';
import { Button, Input, Select, Switch } from '../../../components/admin';

export default function ClientForm({ record = null, options }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: record?.name ?? '',
        email: record?.email ?? '',
        vip: record?.vip ?? false,
        status: record?.status ?? 'prospect',
    });

    const bind = (field) => ({
        value: data[field] ?? '',
        onChange: (event) => setData(field, event.target.value),
        error: errors[field],
    });

    const submit = (event) => {
        event.preventDefault();

        if (record) {
            put(route('admin.clients.update', record.id));
        } else {
            post(route('admin.clients.store'));
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Input label={'Name'} required maxLength={255} {...bind('name')} />
            <Input label={'Email'} required type="email" {...bind('email')} />
            <Switch label={'Vip'} checked={Boolean(data.vip)} onChange={(value) => setData('vip', value)} error={errors.vip} />
            <Select label={'Status'} required options={options.status ?? []} {...bind('status')} />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.clients.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
