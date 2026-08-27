<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SilaSeo\Laravel\SilaSeoServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SilaSeoServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('silaseo.organization', [
            'name' => 'Sila',
            'url' => 'https://example.com',
            'logo' => 'https://example.com/logo.png',
            'same_as' => ['https://twitter.com/sila'],
        ]);
        $app['config']->set('silaseo.defaults.site_name', 'Sila');
    }
}