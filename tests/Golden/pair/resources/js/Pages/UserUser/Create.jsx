import { AdminLayout } from '../../components/admin';
import FollowForm from './partials/FollowForm';

const TITLE = 'Follows';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.user-user.index') }, { label: 'Nouveau' }]}
        >
            <FollowForm options={options} />
        </AdminLayout>
    );
}
