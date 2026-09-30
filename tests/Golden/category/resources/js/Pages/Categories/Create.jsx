import { AdminLayout } from '../../components/admin';
import CategoryForm from './partials/CategoryForm';

const TITLE = 'Categories';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.categories.index') }, { label: 'Nouveau' }]}
        >
            <CategoryForm options={options} />
        </AdminLayout>
    );
}
