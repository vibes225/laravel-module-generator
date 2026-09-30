import { useId } from 'react';

// Interrupteur : `onChange` reçoit un booléen.
export default function Switch({ label, checked = false, onChange, error, disabled = false, id, className = '' }) {
    const autoId = useId();
    const switchId = id ?? autoId;

    return (
        <div className={className}>
            <div className="flex items-center gap-3">
                <button
                    id={switchId}
                    type="button"
                    role="switch"
                    aria-checked={checked}
                    disabled={disabled}
                    onClick={() => onChange?.(!checked)}
                    className={[
                        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors',
                        'focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-50',
                        checked ? 'bg-indigo-600' : 'bg-slate-300',
                    ].join(' ')}
                >
                    <span
                        className={[
                            'inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform',
                            checked ? 'translate-x-5' : 'translate-x-0.5',
                        ].join(' ')}
                    />
                </button>
                {label && (
                    <label htmlFor={switchId} className="cursor-pointer text-sm text-slate-700">
                        {label}
                    </label>
                )}
            </div>
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
