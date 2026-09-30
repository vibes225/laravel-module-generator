import { useForm } from '@inertiajs/react';
import { Button, DatePicker, Input, MultiSelect, Select, Switch, Textarea } from '../../../components/admin';

export default function InvoiceForm({ record = null, options }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        number: record?.number ?? '',
        status: record?.status ?? 'draft',
        amount: record?.amount ?? '',
        issued_at: record?.issued_at ?? '',
        attachment: null,
        is_paid: record?.is_paid ?? false,
        secret: '',
        meta: record?.meta ? JSON.stringify(record.meta, null, 2) : '',
        notes: record?.notes ?? '',
        client_id: record?.client_id ?? '',
        tags: record?.tags ?? [],
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
            post(route('admin.invoices.update', record.id), { forceFormData: true });
        } else {
            post(route('admin.invoices.store'), { forceFormData: true });
        }
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Input label={'Number'} required maxLength={255} {...bind('number')} />
            <Select label={'Status'} required options={options.status ?? []} {...bind('status')} />
            <Input label={'Amount'} required type="number" step="0.01" {...bind('amount')} />
            <DatePicker label={'Issued at'} required type="date" {...bind('issued_at')} />
            <Input
                label={'Attachment'}
                type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
                onChange={(event) => setData('attachment', event.target.files[0] ?? null)}
                hint={record?.attachment_url ? 'Laisser vide pour conserver le fichier actuel.' : undefined}
                error={errors.attachment}
            />
            <Switch label={'Is paid'} checked={Boolean(data.is_paid)} onChange={(value) => setData('is_paid', value)} error={errors.is_paid} />
            <Input
                label={'Secret'}
                type="password"
                autoComplete="new-password" required={!record}
                hint={record ? 'Laisser vide pour conserver le mot de passe actuel.' : undefined}
                {...bind('secret')}
            />
            <Textarea label={'Meta'} rows={6} className="font-mono" {...bind('meta')} />
            <Textarea label={'Notes'} {...bind('notes')} />
            <Select label={'Client'} required options={options.client_id ?? []} {...bind('client_id')} />
            <MultiSelect
                label={'Tags'}
                options={options.tags ?? []}
                value={data.tags ?? []}
                onChange={(value) => setData('tags', value)}
                error={errors.tags}
            />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.invoices.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
