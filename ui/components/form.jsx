// Primitives de formulaire de l'outil (indépendantes du kit publié chez l'hôte).

const control =
    'block w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none';

export function Errors({ messages }) {
    if (!messages || messages.length === 0) {
        return null;
    }

    return (
        <div className="mt-1 space-y-0.5">
            {messages.map((message) => (
                <p key={message} className="text-xs text-red-600">
                    {message}
                </p>
            ))}
        </div>
    );
}

export function Labeled({ label, hint, errors, className = '', children }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-xs font-medium text-slate-600">{label}</span>
            {children}
            {hint && <span className="mt-1 block text-xs text-slate-400">{hint}</span>}
            <Errors messages={errors} />
        </label>
    );
}

export function Text({ label, value, onChange, placeholder, errors, hint, className, list, type = 'text' }) {
    return (
        <Labeled label={label} errors={errors} hint={hint} className={className}>
            <input
                type={type}
                list={list}
                className={control}
                value={value ?? ''}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
            />
        </Labeled>
    );
}

export function NumberInput({ label, value, onChange, errors, className, placeholder }) {
    return (
        <Labeled label={label} errors={errors} className={className}>
            <input
                type="number"
                className={control}
                value={value ?? ''}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value === '' ? undefined : Number(event.target.value))}
            />
        </Labeled>
    );
}

export function Choice({ label, value, onChange, options, errors, className, empty }) {
    return (
        <Labeled label={label} errors={errors} className={className}>
            <select className={control} value={value ?? ''} onChange={(event) => onChange(event.target.value)}>
                {empty !== undefined && <option value="">{empty}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Labeled>
    );
}

export function Toggle({ label, checked, onChange, errors, disabled = false }) {
    return (
        <div>
            <label className={`inline-flex items-center gap-2 text-sm ${disabled ? 'text-slate-400' : 'text-slate-700'}`}>
                <input
                    type="checkbox"
                    disabled={disabled}
                    className="h-4 w-4 rounded border-slate-300 text-indigo-600"
                    checked={Boolean(checked)}
                    onChange={(event) => onChange(event.target.checked)}
                />
                {label}
            </label>
            <Errors messages={errors} />
        </div>
    );
}

export function Section({ title, description, actions, children }) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
            <header className="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-3">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">{title}</h2>
                    {description && <p className="mt-0.5 text-xs text-slate-500">{description}</p>}
                </div>
                {actions}
            </header>
            <div className="space-y-4 p-5">{children}</div>
        </section>
    );
}

export function Button({ variant = 'primary', className = '', children, ...props }) {
    const variants = {
        primary: 'bg-indigo-600 text-white hover:bg-indigo-700',
        secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        danger: 'bg-red-600 text-white hover:bg-red-700',
        ghost: 'text-slate-500 hover:bg-slate-100 hover:text-slate-800',
    };

    return (
        <button
            type="button"
            className={`inline-flex items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}

export function Layout({ title, actions, children }) {
    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-indigo-600 uppercase">Module Generator</p>
                        <h1 className="text-xl font-semibold text-slate-900">{title}</h1>
                    </div>
                    <div className="flex gap-2">{actions}</div>
                </div>
            </header>
            <main className="mx-auto max-w-6xl space-y-6 px-6 py-6">{children}</main>
        </div>
    );
}
