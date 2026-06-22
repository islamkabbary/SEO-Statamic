<template>
    <div class="silaseo-serp" :dir="rtl ? 'rtl' : 'ltr'">
        <div class="silaseo-serp-url">{{ displayUrl }}</div>
        <div class="silaseo-serp-title">{{ title || placeholderTitle }}</div>
        <div class="silaseo-serp-desc">{{ description || placeholderDesc }}</div>
    </div>
</template>

<script>
export default {
    props: {
        title: { type: String, default: '' },
        description: { type: String, default: '' },
        url: { type: String, default: '' },
        rtl: { type: Boolean, default: false },
    },

    computed: {
        placeholderTitle() {
            return this.__('silaseo::messages.serp_title_placeholder');
        },
        placeholderDesc() {
            return this.__('silaseo::messages.serp_desc_placeholder');
        },
        displayUrl() {
            return (this.url || '').replace(/\/+$/, '') || 'https://example.com';
        },
    },
};
</script>

<style>
.silaseo-serp {
    font-family: arial, sans-serif;
    max-width: 600px;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
}
.silaseo-serp-url { color: #202124; font-size: 12px; line-height: 1.3; }
.silaseo-serp-title {
    color: #1a0dab;
    font-size: 18px;
    line-height: 1.3;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.silaseo-serp-desc {
    color: #4d5156;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Dark mode — Google's own dark SERP palette. */
.dark .silaseo-serp { background: #1f1f1f; border-color: rgba(255, 255, 255, 0.12); }
.dark .silaseo-serp-url { color: #bdc1c6; }
.dark .silaseo-serp-title { color: #8ab4f8; }
.dark .silaseo-serp-desc { color: #bdc1c6; }
</style>