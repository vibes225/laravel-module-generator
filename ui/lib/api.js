// Appels JSON vers l'API de l'outil (même moteur que la CLI).

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function url(prefix, path) {
    return `/${prefix}/${path}`.replace(/\/+/g, '/');
}

export async function api(method, target, body) {
    const response = await fetch(target, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));

    return { status: response.status, ok: response.ok, data };
}

/** Retire récursivement les chaînes vides : le moteur dérive alors les valeurs par défaut. */
export function clean(value) {
    if (Array.isArray(value)) {
        return value.map(clean);
    }

    if (value && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value)
                .filter(([, item]) => item !== '' && item !== undefined)
                .map(([key, item]) => [key, clean(item)]),
        );
    }

    return value;
}
