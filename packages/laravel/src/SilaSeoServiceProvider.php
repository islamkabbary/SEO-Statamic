<?php

declare(strict_types=1);

namespace SilaSeo\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use SilaSeo\Core\Cascade\DefaultsResolver;
use SilaSeo\Core\Render\HeadRenderer;
use SilaSeo\Laravel\Http\Middleware\HandleRedirects;
use SilaSeo\Laravel\Http\Middleware\InjectSeo;
use SilaSeo\Laravel\Http\Middleware\LogNotFound;
use SilaSeo\Laravel\Http\Middleware\SetXRobotsTag;
use SilaSeo\Laravel\Routing\SeoRegistry;
use SilaSeo\Laravel\Settings\SettingsRepository;
use SilaSeo\Laravel\View\Components\Head;

final class SilaSeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/silaseo.php', 'silaseo');

        $this->app->singleton(SeoRegistry::class);
        $this->app->singleton(SettingsRepository::class);

        // Request-scoped so SeoContext never bleeds across requests under Octane.
        $this->app->scoped(MetaService::class, static function ($app): MetaService {
            return new MetaService(
                new DefaultsResolver(),
                new HeadRenderer(),
                $app->make(SeoRegistry::class),
                $app->make(SettingsRepository::class),
                (array) $app['config']->get('silaseo', []),
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'silaseo');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerBladeRendering();
        $this->registerMiddlewareAliases();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/silaseo.php' => config_path('silaseo.php'),
            ], 'silaseo-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'silaseo-migrations');
        }
    }

    private function registerBladeRendering(): void
    {
        Blade::component(Head::class, 'seo-head');

        Blade::directive(
            'seoHead',
            static fn (): string => "<?php echo app('" . MetaService::class . "')->head(); ?>",
        );
    }

    private function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('silaseo.redirects', HandleRedirects::class);
        $router->aliasMiddleware('silaseo.robots', SetXRobotsTag::class);
        $router->aliasMiddleware('silaseo.404', LogNotFound::class);
        $router->aliasMiddleware('silaseo.inject', InjectSeo::class);
    }
}