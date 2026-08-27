import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';
import cssInjectedByJs from 'vite-plugin-css-injected-by-js';
import { fileURLToPath, URL } from 'node:url';

/**
 * Statamic 4/5 Control Panel bundle (Vue 2.7).
 *
 * The v6 build (vite.config.js) targets Vue 3 through @statamic/cms and is loaded via
 * Vite. Statamic 4 and 5 run Vue 2.7 and expose it on `window.Vue`, with the base
 * Fieldtype mixin on `window.Fieldtype`. This config compiles the SAME component source
 * with a Vue-2.7 SFC compiler, externalises Vue to the CP's `window.Vue` (never bundling a
 * second copy), inlines the CSS into the JS, and emits ONE classic IIFE script so it can be
 * shipped through AddonServiceProvider::$scripts (a plain <script src>) on 4/5.
 *
 * The only source difference from the v6 build is resolved here: the '#fieldtype-mixin'
 * alias points at the legacy shim (window.Fieldtype) instead of the @statamic/cms export.
 */
export default defineConfig({
    plugins: [
        vue(),
        cssInjectedByJs(),
    ],
    resolve: {
        alias: {
            '#fieldtype-mixin': fileURLToPath(
                new URL('./resources/js/shims/fieldtype-mixin.legacy.js', import.meta.url),
            ),
        },
    },
    build: {
        outDir: 'resources/dist-legacy/js',
        emptyOutDir: true,
        // One self-contained artifact: CSS is inlined into the JS by cssInjectedByJs().
        cssCodeSplit: false,
        lib: {
            entry: fileURLToPath(new URL('./resources/js/cp.js', import.meta.url)),
            formats: ['iife'],
            name: 'SilaseoLegacy',
            fileName: () => 'cp-legacy.js',
            // Required by Vite lib mode when the entry pulls in CSS (this package.json has
            // no `name`). cssInjectedByJs folds the CSS into the JS, so no .css file ships.
            cssFileName: 'cp-legacy',
        },
        rollupOptions: {
            // Vue is provided by the Control Panel at runtime; map every `vue` import to
            // the global so the bundle never carries its own (would break the CP's Vue).
            external: ['vue'],
            output: {
                globals: { vue: 'Vue' },
            },
        },
    },
});
