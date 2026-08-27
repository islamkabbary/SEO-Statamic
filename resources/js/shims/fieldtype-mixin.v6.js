// Statamic 6 build: the Fieldtype mixin is a named export of the core package,
// which resolves to __STATAMIC__.core at runtime. Aliased in via '#fieldtype-mixin'
// (see vite.config.js) so seo-report.vue stays build-agnostic.
export { FieldtypeMixin as default } from '@statamic/cms';
