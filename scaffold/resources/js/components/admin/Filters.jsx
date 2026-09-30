import { useEffect, useRef, useState } from 'react';
import { Search } from 'lucide-react';
import Button from './Button';
import { visitWithQuery } from './query';

/**
 * values  : { search, filter: { nom: valeur } } tel que renvoyé par le serveur
 * filters : [{ name, label, options: [{ value, label }] }] (filtres de type liste)
 */
export default function Filters({ values = {}, filters = [], search: searchable = true, searchPlaceholder = 'Rechercher…' }) {
    const [search, setSearch] = useState(values.search ?? '');
    const skipNext = useRef(true);

    // Recherche différée : évite une requête à chaque frappe.
    useEffect(() => {
        if (skipNext.current) {
            skipNext.current = false;

            return undefined;
        }

        const timer = setTimeout(() => visitWithQuery({ search }), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const filterValue = (name) => values.filter?.[name] ?? '';
    const filterKey = (name) => 'filter[' + name + ']';

    const active = search !== '' || filters.some((filter) => filterValue(filter.name) !== '');

    const reset = () => {
        const changes = { search: null };

        filters.forEach((filter) => {
            changes[filterKey(filter.name)] = null;
        });
        skipNext.current = true;
        setSearch('');
        visitWithQuery(changes);
    };

    return (
        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            {searchable && (
                <div className="relative flex-1">
                    <Search className="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={searchPlaceholder}
                        aria-label="Rechercher"
                        className="block w-full rounded-lg border border-slate-300 bg-white py-2 pr-3 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none"
                    />
                </div>
            )}
            {filters.map((filter) => (
                <select
                    key={filter.name}
                    aria-label={filter.label}
                    value={filterValue(filter.name)}
                    onChange={(event) => visitWithQuery({ [filterKey(filter.name)]: event.target.value })}
                    className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none"
                >
                    <option value="">{filter.label}</option>
                    {filter.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ))}
            {active && (
                <Button variant="ghost" onClick={reset}>
                    Réinitialiser
                </Button>
            )}
        </div>
    );
}
