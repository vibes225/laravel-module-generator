import { AdminLayout } from '../../components/admin';
import ClientTagForm from './partials/ClientTagForm';

const TITLE = 'Client tags';

export default function Edit({ record, options }) {
    return (
        <AdminLayout
            title="Modifier"
            breadcrumbs={[{ label: TITLE, href: route('admin.client-tag.index') }, { label: 'Modifier' }]}
        >
            <ClientTagForm record={record} options={options} />
        </AdminLayout>
    );
}
