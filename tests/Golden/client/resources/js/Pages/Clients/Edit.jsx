import { AdminLayout } from '../../components/admin';
import ClientForm from './partials/ClientForm';

const TITLE = 'Clients';

export default function Edit({ record, options }) {
    return (
        <AdminLayout
            title="Modifier"
            breadcrumbs={[{ label: TITLE, href: route('admin.clients.index') }, { label: 'Modifier' }]}
        >
            <ClientForm record={record} options={options} />
        </AdminLayout>
    );
}
