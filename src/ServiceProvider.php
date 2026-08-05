<?php

declare(strict_types=1);

namespace SilaSeo\Statamic;

use Illuminate\Support\Facades\Event;
use SilaSeo\Statamic\Fieldtypes\SeoReport;
use SilaSeo\Statamic\Gateway\StatamicGateway;
use SilaSeo\Statamic\Gateway\VersionGate;
use SilaSeo\Statamic\IndexNow\SubmitEntryToIndexNow;
use SilaSeo\Statamic\Link\EntryLinkCorpus;
use SilaSeo\Statamic\Support\AssetStrategy;
use SilaSeo\Statamic\Support\Icons;
use SilaSeo\Statamic\Tags\SeoTag;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    /**
     * @var list<class-string>
     */
    protected $tags = [
        SeoTag::class,
    ];

    /**
     * @var list<class-string>
     */
    protected $fieldtypes = [
        SeoReport::class,
    ];

    /**
     * Assigned in {@see register()} rather than here: the committed bundle only
     * runs on Statamic 6, and AddonServiceProvider reads this property while
     * booting, which is after register().
     *
     * @var array{input: list<string>, publicDirectory: string}|null
     */
    protected $vite = null;

    /**
     * @var array<string,string>
     */
    protected $routes = [
        'cp' => __DIR__ . '/../routes/cp.php',
        'web' => __DIR__ . '/../routes/web.php',
    ];

    /**
     * @var array{input: list<string>, publicDirectory: string}
     */
    private const VITE_CONFIG = [
        'input' => [
            'resources/js/cp.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function register(): void
    {
        // The Control Panel bundle is compiled against Statamic 6 -- it reads the
        // __STATAMIC__ global and Vue 3 APIs, neither of which exists in the Vue 2.7
        // Control Panel of Statamic 4 and 5. Registering it there would fail at load.
        //
        // $fieldtypes is deliberately NOT gated the same way: an unregistered handle
        // makes FieldtypeRepository::find() throw, which would break the publish form
        // of every blueprint importing the SEO fieldset. SeoReport degrades to a
        // display-only component instead.
        if (AssetStrategy::current()->shipsVueComponents()) {
            $this->vite = self::VITE_CONFIG;
        }

        parent::register();

        $this->app->singleton(StatamicGateway::class, static fn (): StatamicGateway => VersionGate::driver());
        $this->app->singleton(EntryMapper::class);
        $this->app->scoped(EntrySeo::class);
    }

    public function bootAddon(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'silaseo');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'silaseo');

        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', \SilaSeo\Statamic\Http\Middleware\HandleRedirects::class);
        $router->pushMiddlewareToGroup('web', \SilaSeo\Statamic\Http\Middleware\LogNotFound::class);

        Event::listen([EntrySaved::class, EntryDeleted::class], static function (): void {
            EntryLinkCorpus::flush();
        });

        Event::listen([EntrySaved::class, EntryDeleted::class], SubmitEntryToIndexNow::class . '@handle');

        $this->bootNav();

        $this->publishes([
            __DIR__ . '/../resources/fieldsets/seo.yaml' => resource_path('fieldsets/seo.yaml'),
        ], 'silaseo-fieldset');
    }

    private function bootNav(): void
    {
        Nav::extend(static function ($nav): void {
            $nav->create(__('silaseo::messages.redirects_title'))
                ->section('SEO')
                ->route('silaseo.redirects')
                ->icon(Icons::REDIRECTS);

            $nav->create(__('silaseo::messages.notfound_title'))
                ->section('SEO')
                ->route('silaseo.404-log')
                ->icon(Icons::NOT_FOUND);
        });
    }
}