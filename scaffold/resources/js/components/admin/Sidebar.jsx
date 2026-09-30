import { Link, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import Icon from './icons';

// menu : [{ key, label, items: [{ label, url, icon }] }] (prop partagée `admin.menu`)
function isActive(currentUrl, itemUrl) {
    const current = currentUrl.split('?')[0];
    const path = new URL(itemUrl, 'http://localhost').pathname;

    return current === path || current.startsWith(path + '/');
}

export default function Sidebar({ menu = [], open = false, onClose }) {
    const { url, props } = usePage();
    const appName = props.appName ?? 'Administration';

    return (
        <>
            {open && (
                <div className="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" onClick={onClose} aria-hidden="true" />
            )}
            <aside
                className={[
                    'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform',
                    'lg:static lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                ].join(' ')}
            >
                <div className="flex h-16 items-center justify-between border-b border-slate-200 px-5">
                    <span className="text-base font-semibold text-slate-900">{appName}</span>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded p-1 text-slate-400 lg:hidden"
                        aria-label="Fermer le menu"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
                <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Navigation principale">
                    {menu.map((group) => (
                        <div key={group.key ?? group.label}>
                            {group.label && (
                                <p className="mb-2 px-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">
                                    {group.label}
                                </p>
                            )}
                            <ul className="space-y-1">
                                {group.items.map((item) => {
                                    const active = isActive(url, item.url);

                                    return (
                                        <li key={item.url}>
                                            <Link
                                                href={item.url}
                                                onClick={onClose}
                                                aria-current={active ? 'page' : undefined}
                                                className={[
                                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                                                    active
                                                        ? 'bg-indigo-50 text-indigo-700'
                                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                                ].join(' ')}
                                            >
                                                <Icon name={item.icon} />
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>
            </aside>
        </>
    );
}
