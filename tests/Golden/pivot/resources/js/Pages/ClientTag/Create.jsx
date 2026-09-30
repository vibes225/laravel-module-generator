import { AdminLayout } from '../../components/admin';
import ClientTagForm from './partials/ClientTagForm';

const TITLE = 'Client tags';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.client-tag.index') }, { label: 'Nouveau' }]}
        >
            <ClientTagForm options={options} />
        </AdminLayout>
    );
}
