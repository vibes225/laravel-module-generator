import { AdminLayout } from '../../components/admin';
import InvoiceForm from './partials/InvoiceForm';

const TITLE = 'Invoices';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.invoices.index') }, { label: 'Nouveau' }]}
        >
            <InvoiceForm options={options} />
        </AdminLayout>
    );
}
