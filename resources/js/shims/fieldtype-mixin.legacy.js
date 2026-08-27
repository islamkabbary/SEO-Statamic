// Statamic 4/5 build: core registers the Vue 2.7 Fieldtype mixin as a runtime global
// (window.Fieldtype = Fieldtype, set in bootstrap/mixins.js before deferred addon
// scripts run). Aliased in via '#fieldtype-mixin' (see vite.legacy.config.js).
export default window.Fieldtype;
