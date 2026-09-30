import { AdminLayout } from '../../components/admin';
import TaggableForm from './partials/TaggableForm';

const TITLE = 'Taggables';

export default function Create({ options }) {
    return (
        <AdminLayout
            title="Nouveau"
            breadcrumbs={[{ label: TITLE, href: route('admin.taggables.index') }, { label: 'Nouveau' }]}
        >
            <TaggableForm options={options} />
        </AdminLayout>
    );
}
