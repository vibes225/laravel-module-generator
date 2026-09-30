import './app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/*.jsx', { eager: true });

createInertiaApp({
    resolve: (name) => pages[`./Pages/${name}.jsx`],
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
