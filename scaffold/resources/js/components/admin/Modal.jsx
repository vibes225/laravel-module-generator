import { useEffect, useRef } from 'react';
import { X } from 'lucide-react';

const widths = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
};

// Basé sur l'élément natif <dialog> : focus, Échap et arrière-plan gérés par le navigateur.
export default function Modal({ open, onClose, title, children, footer, size = 'md' }) {
    const ref = useRef(null);

    useEffect(() => {
        const dialog = ref.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            onClose={() => onClose?.()}
            onClick={(event) => {
                if (event.target === ref.current) {
                    onClose?.();
                }
            }}
            className={[
                'm-auto w-full rounded-xl p-0 shadow-xl backdrop:bg-slate-900/50',
                widths[size] ?? widths.md,
            ].join(' ')}
        >
            {open && (
                <div>
                    <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                        <button
                            type="button"
                            onClick={() => onClose?.()}
                            className="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Fermer"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                    <div className="px-5 py-4 text-sm text-slate-700">{children}</div>
                    {footer && (
                        <div className="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
                            {footer}
                        </div>
                    )}
                </div>
            )}
        </dialog>
    );
}
