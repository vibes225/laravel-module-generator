import { useId } from 'react';

export default function Checkbox({ label, error, id, className = '', ...props }) {
    const autoId = useId();
    const checkboxId = id ?? autoId;

    return (
        <div className={className}>
            <label htmlFor={checkboxId} className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input
                    id={checkboxId}
                    type="checkbox"
                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-200"
                    {...props}
                />
                {label}
            </label>
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
