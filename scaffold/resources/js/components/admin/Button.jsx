import { Link } from '@inertiajs/react';

const variants = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-200',
    secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-200',
    ghost: 'text-slate-600 hover:bg-slate-100 focus:ring-slate-200',
};

const sizes = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
};

// Avec `href`, rend un lien Inertia ; sinon un bouton (`type="button"` par défaut).
export default function Button({
    variant = 'primary',
    size = 'md',
    href,
    loading = false,
    disabled = false,
    type = 'button',
    className = '',
    children,
    ...props
}) {
    const classes = [
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors',
        'focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50',
        variants[variant],
        sizes[size],
        className,
    ].join(' ');

    if (href) {
        return (
            <Link href={href} className={classes} {...props}>
                {children}
            </Link>
        );
    }

    return (
        <button type={type} disabled={disabled || loading} className={classes} {...props}>
            {loading && (
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
            )}
            {children}
        </button>
    );
}
