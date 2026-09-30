import { AdminLayout } from '../../components/admin';
import CategoryForm from './partials/CategoryForm';

const TITLE = 'Categories';

export default function Edit({ record, options }) {
    return (
        <AdminLayout
            title="Modifier"
            breadcrumbs={[{ label: TITLE, href: route('admin.categories.index') }, { label: 'Modifier' }]}
        >
            <CategoryForm record={record} options={options} />
        </AdminLayout>
    );
}
