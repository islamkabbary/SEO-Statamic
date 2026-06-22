import SeoReportFieldtype from './components/seo-report.vue';

Statamic.booting(() => {
    Statamic.$components.register('seo_report-fieldtype', SeoReportFieldtype);
});