{{-- Shared SilaSEO CP styling. Pushed to the head stack (NOT the content area,
     which lives inside the Vue-mounted #statamic div where <style> is stripped).
     Light mode uses Statamic's content surface vars; dark mode layers a faint
     light overlay so panels/buttons sit a step above the near-black body. --}}
@push('head')
<style>
    .silaseo-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .silaseo-muted { opacity: 0.6; }

    /* Elevated panel surface */
    .silaseo-card {
        background: var(--theme-color-content-bg, #fff);
        border: 1px solid var(--theme-color-content-border, #e5e7eb);
        border-radius: 0.75rem;
    }
    .dark .silaseo-card {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.10);
    }

    /* Table */
    .silaseo-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .silaseo-table th, .silaseo-table td { padding: 0.7rem 0.95rem; text-align: start; vertical-align: middle; }
    .silaseo-table thead th {
        font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;
        opacity: 0.55; border-bottom: 1px solid var(--theme-color-content-border, #e5e7eb);
    }
    .dark .silaseo-table thead th { border-bottom-color: rgba(255, 255, 255, 0.10); }
    .silaseo-table tbody tr + tr td { border-top: 1px solid var(--theme-color-content-border, #f1f1f1); }
    .dark .silaseo-table tbody tr + tr td { border-top-color: rgba(255, 255, 255, 0.06); }
    .silaseo-table tbody tr:hover td { background: rgba(0, 0, 0, 0.025); }
    .dark .silaseo-table tbody tr:hover td { background: rgba(255, 255, 255, 0.03); }

    /* Links */
    .silaseo-link { color: var(--theme-color-ui-accent-text, #4f46e5); text-decoration: none; }
    .silaseo-link:hover { text-decoration: underline; }

    /* Buttons */
    .silaseo-btn, .silaseo-btn-accent, .silaseo-btn-danger {
        display: inline-block; cursor: pointer; border-radius: 0.375rem;
        padding: 0.45rem 0.85rem; font-size: 0.8rem; line-height: 1.2; border: 1px solid transparent; font-weight: 500;
    }
    .silaseo-btn { color: inherit; background: var(--theme-color-content-bg, #fff); border-color: var(--theme-color-content-border, #d1d5db); }
    .dark .silaseo-btn { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.15); }
    .silaseo-btn:hover { opacity: 0.8; }
    .silaseo-btn-accent { color: #fff; background: var(--theme-color-ui-accent-bg, #4f46e5); }
    .silaseo-btn-accent:hover { opacity: 0.9; }
    .silaseo-btn-danger { color: var(--theme-color-danger, #dc2626); border-color: var(--theme-color-danger, #dc2626); background: transparent; }
    .silaseo-btn-danger:hover { background: color-mix(in oklab, var(--theme-color-danger, #dc2626) 14%, transparent); }

    .silaseo-empty { padding: 2.5rem 1rem; text-align: center; opacity: 0.6; }
</style>
@endpush