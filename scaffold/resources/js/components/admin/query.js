import { router } from '@inertiajs/react';

// Convention des listes : ?search=…&sort=…&direction=asc|desc&filter[nom]=…&page=…

export function currentQuery() {
    return new URLSearchParams(window.location.search);
}

/**
 * Applique des changements à la query string courante et navigue.
 * Une valeur vide supprime le paramètre ; la page revient à 1 sauf si `page` est modifiée.
 */
export function visitWithQuery(changes, options = {}) {
    const params = currentQuery();

    Object.entries(changes).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            params.delete(key);
        } else {
            params.set(key, String(value));
        }
    });

    if (!('page' in changes)) {
        params.delete('page');
    }

    const queryString = params.toString();

    router.get(
        window.location.pathname + (queryString ? '?' + queryString : ''),
        {},
        { preserveState: true, preserveScroll: true, replace: true, ...options },
    );
}
