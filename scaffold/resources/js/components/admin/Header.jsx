import { usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';

export default function Header({ onMenuClick }) {
    const user = usePage().props.auth?.user;

    return (
        <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
            <button
                type="button"
                onClick={onMenuClick}
                className="rounded p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                aria-label="Ouvrir le menu"
            >
                <Menu className="h-5 w-5" />
            </button>
            <div className="ml-auto flex items-center gap-3">
                {user && (
                    <>
                        <span className="text-sm text-slate-600">{user.name}</span>
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {String(user.name ?? '?')
                                .charAt(0)
                                .toUpperCase()}
                        </span>
                    </>
                )}
            </div>
        </header>
    );
}
