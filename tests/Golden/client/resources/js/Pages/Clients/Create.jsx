import { AdminLayout } from '../../components/admin';
import ClientForm from './partials/ClientForm';

const TITLE = 'Clients';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.clients.index') }, { label: 'Nouveau' }]}
        >
            <ClientForm options={options} />
        </AdminLayout>
    );
}
