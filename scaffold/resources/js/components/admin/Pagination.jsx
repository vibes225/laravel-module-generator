import { Link } from '@inertiajs/react';

// Reçoit le paginateur Laravel sérialisé (paginate()) : { links, from, to, total }.
function label(link, index, last) {
    if (index === 0) {
        return 'Précédent';
    }

    if (index === last) {
        return 'Suivant';
    }

    return link.label;
}

export default function Pagination({ paginator }) {
    const { links = [], from, to, total } = paginator;

    if (links.length <= 3) {
        return total > 0 ? (
            <p className="mt-4 text-sm text-slate-500">
                {total} résultat{total > 1 ? 's' : ''}
            </p>
        ) : null;
    }

    return (
        <div className="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-slate-500">
                {from} à {to} sur {total} résultats
            </p>
            <nav className="flex flex-wrap gap-1" aria-label="Pagination">
                {links.map((link, index) => {
                    const classes = [
                        'min-w-9 rounded-lg border px-3 py-1.5 text-center text-sm',
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-slate-300 bg-white text-slate-700',
                        link.url ? 'hover:bg-slate-50' : 'cursor-not-allowed opacity-50',
                    ].join(' ');
                    const text = label(link, index, links.length - 1);

                    return link.url ? (
                        <Link key={index} href={link.url} preserveScroll className={classes}>
                            {text}
                        </Link>
                    ) : (
                        <span key={index} className={classes}>
                            {text}
                        </span>
                    );
                })}
            </nav>
        </div>
    );
}
