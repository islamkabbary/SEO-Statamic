<template>
    <div class="silaseo-social" :dir="rtl ? 'rtl' : 'ltr'">
        <div class="silaseo-social-img">
            <span>{{ __('silaseo::messages.social_image') }}</span>
        </div>
        <div class="silaseo-social-body">
            <div class="silaseo-social-domain">{{ domain }}</div>
            <div class="silaseo-social-title">{{ title || '—' }}</div>
            <div class="silaseo-social-desc">{{ description }}</div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: { type: String, default: '' },
        description: { type: String, default: '' },
        domain: { type: String, default: '' },
        rtl: { type: Boolean, default: false },
    },
};
</script>

<style>
.silaseo-social {
    max-width: 600px;
    border: 1px solid #dadde1;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.silaseo-social-img {
    background: #e4e6eb;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8a8d91;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.silaseo-social-body { padding: 10px 12px; }
.silaseo-social-domain { color: #606770; font-size: 11px; text-transform: uppercase; }
.silaseo-social-title { color: #1d2129; font-weight: 600; font-size: 15px; margin-top: 2px; }
.silaseo-social-desc {
    color: #606770;
    font-size: 13px;
    margin-top: 2px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Dark mode — Facebook/X dark card palette. */
.dark .silaseo-social { background: #242526; border-color: #3a3b3c; }
.dark .silaseo-social-img { background: #3a3b3c; color: #b0b3b8; }
.dark .silaseo-social-domain { color: #b0b3b8; }
.dark .silaseo-social-title { color: #e4e6eb; }
.dark .silaseo-social-desc { color: #b0b3b8; }
</style>