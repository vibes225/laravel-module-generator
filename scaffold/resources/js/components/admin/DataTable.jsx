import { ChevronDown, ChevronUp } from 'lucide-react';
import EmptyState from './EmptyState';
import { visitWithQuery } from './query';

/**
 * columns : [{ key, label, sortable?, align?, className?, render?(row) }]
 * sort    : { by, direction } tel que renvoyé par le serveur
 * actions : (row) => noeud affiché dans la dernière colonne
 * rowKey  : nom de colonne ou fonction (row) => clé unique
 */
export default function DataTable({ columns, rows, rowKey = 'id', sort = {}, actions, emptyState }) {
    const toggleSort = (key) => {
        const direction = sort.by === key && sort.direction === 'asc' ? 'desc' : 'asc';

        visitWithQuery({ sort: key, direction });
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            {columns.map((column) => {
                                const active = sort.by === column.key;

                                return (
                                    <th
                                        key={column.key}
                                        scope="col"
                                        className={[
                                            'px-4 py-3 font-medium text-slate-600',
                                            column.align === 'right' ? 'text-right' : 'text-left',
                                        ].join(' ')}
                                    >
                                        {column.sortable ? (
                                            <button
                                                type="button"
                                                onClick={() => toggleSort(column.key)}
                                                className="inline-flex items-center gap-1 hover:text-slate-900"
                                            >
                                                {column.label}
                                                {active &&
                                                    (sort.direction === 'desc' ? (
                                                        <ChevronDown className="h-3.5 w-3.5" />
                                                    ) : (
                                                        <ChevronUp className="h-3.5 w-3.5" />
                                                    ))}
                                            </button>
                                        ) : (
                                            column.label
                                        )}
                                    </th>
                                );
                            })}
                            {actions && <th scope="col" className="px-4 py-3" />}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <tr key={typeof rowKey === 'function' ? rowKey(row) : row[rowKey]} className="hover:bg-slate-50">
                                {columns.map((column) => (
                                    <td
                                        key={column.key}
                                        className={[
                                            'px-4 py-3 text-slate-700',
                                            column.align === 'right' ? 'text-right' : 'text-left',
                                            column.className ?? '',
                                        ].join(' ')}
                                    >
                                        {column.render ? column.render(row) : (row[column.key] ?? '—')}
                                    </td>
                                ))}
                                {actions && (
                                    <td className="px-4 py-3 text-right whitespace-nowrap">{actions(row)}</td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {rows.length === 0 && (emptyState ?? <EmptyState title="Aucun résultat" />)}
        </div>
    );
}
