import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

// Bundle unique à noms fixes (dist/app.js, dist/app.css) servi par le package lui-même.
export default defineConfig({
    plugins: [react(), tailwindcss()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        rollupOptions: {
            input: 'ui/app.jsx',
            output: {
                entryFileNames: 'app.js',
                assetFileNames: 'app[extname]',
                inlineDynamicImports: true,
            },
        },
    },
});
