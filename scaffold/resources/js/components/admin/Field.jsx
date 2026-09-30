// Enveloppe commune des champs de formulaire : libellé, aide et message d'erreur.

export function controlClasses(error) {
    return [
        'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm',
        'placeholder:text-slate-400 focus:outline-none focus:ring-2',
        'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
        error
            ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-200',
    ].join(' ');
}

export default function Field({ id, label, error, hint, required, className = '', children }) {
    return (
        <div className={className}>
            {label && (
                <label htmlFor={id} className="mb-1 block text-sm font-medium text-slate-700">
                    {label}
                    {required && <span className="ml-0.5 text-red-500">*</span>}
                </label>
            )}
            {children}
            {hint && !error && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
