import { AdminLayout } from '../../components/admin';
import TaggableForm from './partials/TaggableForm';

const TITLE = 'Taggables';

export default function Edit({ record, options }) {
    return (
        <AdminLayout
            title="Modifier"
            breadcrumbs={[{ label: TITLE, href: route('admin.taggables.index') }, { label: 'Modifier' }]}
        >
            <TaggableForm record={record} options={options} />
        </AdminLayout>
    );
}
