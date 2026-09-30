import { useForm } from '@inertiajs/react';
import { Button, Select } from '../../../components/admin';

export default function FollowForm({ record = null, options }) {
    const { data, setData, post, processing, errors } = useForm({
        follower_id: record?.follower_id ?? '',
        followed_id: record?.followed_id ?? '',
    });

    const bind = (field) => ({
        value: data[field] ?? '',
        onChange: (event) => setData(field, event.target.value),
        error: errors[field],
    });

    const submit = (event) => {
        event.preventDefault();

        post(route('admin.user-user.store'));
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <Select label={'User'} required options={options.follower_id ?? []} {...bind('follower_id')} />
            <Select label={'User'} required options={options.followed_id ?? []} {...bind('followed_id')} />
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <Button variant="secondary" href={route('admin.user-user.index')}>
                    Annuler
                </Button>
                <Button type="submit" loading={processing}>
                    Enregistrer
                </Button>
            </div>
        </form>
    );
}
