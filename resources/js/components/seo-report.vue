<template>
    <div class="silaseo-report">
        <serp-preview :title="previewTitle" :url="previewUrl" :description="previewDescription" :rtl="rtl" />
        <social-preview class="silaseo-mt" :title="previewTitle" :description="previewDescription" :domain="domain" :rtl="rtl" />

        <div v-if="report" class="silaseo-mt">
            <div class="silaseo-head">
                <div class="silaseo-score" :class="`silaseo-${report.colour}`">{{ report.score }}</div>
                <div>
                    <div class="font-bold" :class="`silaseo-text-${report.colour}`">{{ label }}</div>
                    <div class="text-2xs text-gray-500 uppercase tracking-wide">
                        {{ __('silaseo::messages.seo_score') }}<span v-if="loading"> …</span>
                    </div>
                </div>
            </div>

            <ul class="silaseo-checks">
                <li v-for="r in report.results" :key="r.id" class="flex items-start gap-2 py-1 text-sm">
                    <span class="silaseo-dot" :class="`silaseo-text-${dotColour(r.status)}`">●</span>
                    <span>{{ r.message }}</span>
                </li>
            </ul>
        </div>

        <div v-else class="silaseo-mt text-gray-500 text-sm">
            {{ __('silaseo::messages.save_to_analyse') }}
        </div>

        <div v-if="suggestions.length" class="silaseo-mt silaseo-links">
            <div class="text-2xs text-gray-500 uppercase tracking-wide silaseo-mb">
                {{ __('silaseo::messages.link_suggestions') }}
            </div>
            <ul>
                <li v-for="s in suggestions" :key="s.id" class="py-1 text-sm">
                    <a :href="s.url" target="_blank" rel="noopener" class="silaseo-text-link font-medium">{{ s.title }}</a>
                    <span class="text-2xs text-gray-500"> · {{ s.url }}</span>
                    <div class="silaseo-chips">
                        <span v-for="m in s.matches" :key="m.phrase" class="silaseo-chip">{{ m.phrase }} ×{{ m.count }}</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { FieldtypeMixin } from '@statamic/cms';
import SerpPreview from './serp-preview.vue';
import SocialPreview from './social-preview.vue';

export default {
    mixins: [FieldtypeMixin],

    components: { SerpPreview, SocialPreview },

    data() {
        return {
            report: this.meta.report,
            suggestions: [],
            loading: false,
            timer: null,
        };
    },

    mounted() {
        this.fetchSuggestions();
    },

    computed: {
        formValues() {
            return this.values || {};
        },
        rtl() {
            return (this.meta.locale || '').slice(0, 2) === 'ar';
        },
        previewTitle() {
            return this.formValues.seo_title || this.formValues.title || '';
        },
        previewDescription() {
            return this.formValues.seo_description || this.formValues.description || '';
        },
        previewUrl() {
            return (this.meta.site_url || '') + '/' + (this.formValues.slug || '');
        },
        domain() {
            try {
                return new URL(this.meta.site_url).host;
            } catch (e) {
                return this.meta.site_url || '';
            }
        },
        label() {
            if (!this.report) return '';
            return {
                green: this.__('silaseo::messages.score_good'),
                orange: this.__('silaseo::messages.score_ok'),
                red: this.__('silaseo::messages.score_poor'),
            }[this.report.colour];
        },
    },

    watch: {
        values: {
            deep: true,
            handler() {
                this.queueAnalysis();
            },
        },
    },

    methods: {
        dotColour(status) {
            return { pass: 'green', warn: 'orange', fail: 'red' }[status] || 'gray';
        },

        queueAnalysis() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.analyse();
                this.fetchSuggestions();
            }, 500);
        },

        http() {
            return (window.Statamic && window.Statamic.$axios) || this.$axios || window.axios;
        },

        fetchSuggestions() {
            const http = this.http();

            if (!http || !this.meta.link_endpoint) {
                return;
            }

            http.post(this.meta.link_endpoint, {
                values: this.values,
                locale: this.meta.locale,
                id: this.formValues.id,
            })
                .then((response) => {
                    this.suggestions = (response.data && response.data.suggestions) || [];
                })
                .catch(() => {});
        },

        analyse() {
            const http = this.http();

            if (!http || !this.meta.endpoint) {
                return;
            }

            this.loading = true;

            http.post(this.meta.endpoint, {
                values: this.values,
                locale: this.meta.locale,
                page_type: this.meta.page_type,
            })
                .then((response) => {
                    this.report = response.data;
                })
                .catch(() => {})
                .then(() => {
                    this.loading = false;
                });
        },
    },
};
</script>

<style>
.silaseo-mt { margin-top: 1rem; }
.silaseo-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; }
.silaseo-score {
    width: 3rem;
    height: 3rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.silaseo-green { background-color: #16a34a; }
.silaseo-orange { background-color: #f59e0b; }
.silaseo-red { background-color: #dc2626; }
.silaseo-text-green { color: #16a34a; }
.silaseo-text-orange { color: #f59e0b; }
.silaseo-text-red { color: #dc2626; }
.silaseo-text-gray { color: #6b7280; }
.silaseo-checks { border-top: 1px solid #e5e7eb; }
.silaseo-dot { line-height: 1.4; }
.silaseo-mb { margin-bottom: 0.5rem; }
.silaseo-links { border-top: 1px solid #e5e7eb; padding-top: 0.75rem; }
.silaseo-links li { border-bottom: 1px solid #f3f4f6; }
.silaseo-text-link { color: #2563eb; }
.silaseo-chips { margin-top: 0.25rem; }
.silaseo-chip {
    display: inline-block;
    background-color: #f3f4f6;
    color: #4b5563;
    border-radius: 9999px;
    padding: 0 0.5rem;
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
    font-size: 0.7rem;
}
[dir="rtl"] .silaseo-chip { margin-right: 0; margin-left: 0.25rem; }

/* Dark mode — align borders/chips/links with the CP theme. */
.dark .silaseo-checks { border-top-color: rgba(255, 255, 255, 0.12); }
.dark .silaseo-links { border-top-color: rgba(255, 255, 255, 0.12); }
.dark .silaseo-links li { border-bottom-color: rgba(255, 255, 255, 0.07); }
.dark .silaseo-chip { background-color: rgba(255, 255, 255, 0.08); color: #cbd5e1; }
.dark .silaseo-text-gray { color: #9ca3af; }
.dark .silaseo-text-link { color: #8ab4f8; }
</style>