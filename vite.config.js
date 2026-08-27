import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        statamic(),
        laravel({
            input: [
                'resources/js/cp.js',
            ],
            publicDirectory: 'resources/dist',
        }),
    ],
    resolve: {
        alias: {
            // seo-report.vue imports the Fieldtype mixin from '#fieldtype-mixin' so a
            // single source compiles under both builds. The v6 shim re-exports
            // FieldtypeMixin from @statamic/cms; the legacy build aliases it elsewhere.
            '#fieldtype-mixin': fileURLToPath(
                new URL('./resources/js/shims/fieldtype-mixin.v6.js', import.meta.url),
            ),
        },
    },
});
