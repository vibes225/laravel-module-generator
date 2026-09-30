import { AdminLayout } from '../../components/admin';
import InvoiceForm from './partials/InvoiceForm';

const TITLE = 'Invoices';

export default function Edit({ record, options }) {
    return (
        <AdminLayout
            title="Modifier"
            breadcrumbs={[{ label: TITLE, href: route('admin.invoices.index') }, { label: 'Modifier' }]}
        >
            <InvoiceForm record={record} options={options} />
        </AdminLayout>
    );
}
