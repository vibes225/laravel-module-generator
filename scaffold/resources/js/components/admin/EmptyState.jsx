import Icon from './icons';

export default function EmptyState({ icon = 'inbox', title = 'Aucun élément', description, action }) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
            <div className="rounded-full bg-slate-100 p-3 text-slate-400">
                <Icon name={icon} className="h-6 w-6" />
            </div>
            <h3 className="mt-4 text-sm font-semibold text-slate-900">{title}</h3>
            {description && <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
