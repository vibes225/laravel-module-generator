import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

// items : [{ label, href? }], le dernier élément est la page courante.
export default function Breadcrumb({ items = [] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Fil d'Ariane" className="mb-2 flex items-center gap-1 text-sm text-slate-500">
            {items.map((item, index) => {
                const last = index === items.length - 1;

                return (
                    <span key={index} className="flex items-center gap-1">
                        {item.href && !last ? (
                            <Link href={item.href} className="hover:text-slate-900">
                                {item.label}
                            </Link>
                        ) : (
                            <span className={last ? 'font-medium text-slate-700' : ''}>{item.label}</span>
                        )}
                        {!last && <ChevronRight className="h-3.5 w-3.5" />}
                    </span>
                );
            })}
        </nav>
    );
}
