import { useState } from 'react';
import { AlertTriangle, CheckCircle2, FileCode, Info, XCircle } from 'lucide-react';

export default function PlanPreview({ plan }) {
    const [open, setOpen] = useState(plan.files[0]?.path ?? null);
    const current = plan.files.find((file) => file.path === open);
    const conflicts = Object.entries(plan.conflicts ?? {});

    return (
        <div className="space-y-4">
            <div
                className={`flex items-center gap-2 rounded-lg border px-4 py-3 text-sm ${plan.executable ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'}`}
            >
                {plan.executable ? <CheckCircle2 className="h-4 w-4" /> : <XCircle className="h-4 w-4" />}
                {plan.executable
                    ? `${plan.files.length} fichier(s) prêts à être générés.`
                    : 'Plan non exécutable : aucun fichier ne sera écrit (pas d\'écrasement possible).'}
            </div>

            {plan.notes.map((note) => (
                <p key={note} className="flex gap-2 text-sm text-amber-800">
                    <Info className="mt-0.5 h-4 w-4 shrink-0" /> {note}
                </p>
            ))}

            {conflicts.map(([path, messages]) =>
                messages.map((message) => (
                    <p key={path + message} className="flex gap-2 text-sm text-red-700">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            <code className="font-mono text-xs">{path}</code> : {message}
                        </span>
                    </p>
                )),
            )}

            {plan.missing.map((command) => (
                <p key={command} className="flex gap-2 text-sm text-red-700">
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" /> Prérequis manquant, lancer :
                    <code className="rounded bg-red-50 px-1.5 font-mono text-xs">{command}</code>
                </p>
            ))}

            <div className="grid gap-4 lg:grid-cols-[18rem_1fr]">
                <ul className="max-h-[32rem] space-y-0.5 overflow-y-auto">
                    {plan.files.map((file) => (
                        <li key={file.path}>
                            <button
                                type="button"
                                onClick={() => setOpen(file.path)}
                                className={`flex w-full items-start gap-2 rounded px-2 py-1.5 text-left text-xs ${open === file.path ? 'bg-indigo-50 text-indigo-800' : 'text-slate-600 hover:bg-slate-100'}`}
                            >
                                <FileCode className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                <span className="break-all font-mono">{file.path}</span>
                            </button>
                        </li>
                    ))}
                </ul>
                {current && (
                    <div className="min-w-0 overflow-hidden rounded-lg border border-slate-200">
                        <div className="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">{current.label}</div>
                        <pre className="max-h-[32rem] overflow-auto bg-slate-900 p-4 text-xs leading-relaxed text-slate-100">
                            {current.content}
                        </pre>
                    </div>
                )}
            </div>
        </div>
    );
}
