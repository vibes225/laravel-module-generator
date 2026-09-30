// Formatage français des dates, montants et nombres.

const EMPTY = '—';

function isEmpty(value) {
    return value === null || value === undefined || value === '';
}

function toDate(value) {
    if (value instanceof Date) {
        return value;
    }

    const text = String(value);

    // Une date seule (AAAA-MM-JJ) est interprétée en heure locale pour éviter tout décalage de fuseau.
    if (text.length === 10 && text.charAt(4) === '-' && text.charAt(7) === '-') {
        const [year, month, day] = text.split('-').map(Number);

        return new Date(year, month - 1, day);
    }

    return new Date(text);
}

export function formatNumber(value, options = {}) {
    if (isEmpty(value) || Number.isNaN(Number(value))) {
        return EMPTY;
    }

    return new Intl.NumberFormat('fr-FR', options).format(Number(value));
}

export function formatMoney(value, currency = 'EUR') {
    return formatNumber(value, { style: 'currency', currency });
}

export function formatDate(value) {
    if (isEmpty(value)) {
        return EMPTY;
    }

    const date = toDate(value);

    if (Number.isNaN(date.getTime())) {
        return EMPTY;
    }

    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
}

export function formatDateTime(value) {
    if (isEmpty(value)) {
        return EMPTY;
    }

    const date = toDate(value);

    if (Number.isNaN(date.getTime())) {
        return EMPTY;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export function formatBoolean(value) {
    return value ? 'Oui' : 'Non';
}
