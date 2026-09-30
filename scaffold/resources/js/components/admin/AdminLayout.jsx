import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import Breadcrumb from './Breadcrumb';
import Header from './Header';
import Sidebar from './Sidebar';

// Prop partagée attendue : admin = { menu: [...], flash: { success, error } } (facultatives).
export default function AdminLayout({ title, breadcrumbs = [], actions, children }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const { props, flash: pageFlash } = usePage();
    const admin = props.admin ?? {};
    const flash = { ...admin.flash, ...pageFlash };

    return (
        <div className="flex min-h-screen bg-slate-50">
            <Head title={title} />
            <Sidebar menu={admin.menu ?? []} open={menuOpen} onClose={() => setMenuOpen(false)} />
            <div className="flex min-w-0 flex-1 flex-col">
                <Header onMenuClick={() => setMenuOpen(true)} />
                <main className="flex-1 p-4 sm:p-6">
                    {flash.success && (
                        <div
                            role="status"
                            className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                        >
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div
                            role="alert"
                            className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                        >
                            {flash.error}
                        </div>
                    )}
                    <Breadcrumb items={breadcrumbs} />
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <h1 className="text-2xl font-semibold text-slate-900">{title}</h1>
                        {actions && <div className="flex items-center gap-2">{actions}</div>}
                    </div>
                    {children}
                </main>
            </div>
        </div>
    );
}
