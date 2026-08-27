<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SilaSeo\Laravel\Models\SeoRedirect;
use SilaSeo\Laravel\Redirects\RedirectRepository;

/**
 * Editors type redirect paths by hand, so the `from` column holds every spelling
 * of the same path. The repository used to normalise only the *lookup* side while
 * keying its cache on the raw column, so any row not already stored as `/path`
 * was unreachable -- the redirect simply never fired, silently.
 */
final class RedirectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): RedirectRepository
    {
        return $this->app->make(RedirectRepository::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function equivalentSpellings(): array
    {
        return [
            'canonical stored, canonical requested' => ['/about', '/about'],
            'canonical stored, bare requested' => ['/about', 'about'],
            'canonical stored, trailing slash requested' => ['/about', '/about/'],
            'bare stored, canonical requested' => ['about', '/about'],
            'bare stored, bare requested' => ['about', 'about'],
            'trailing slash stored, canonical requested' => ['/about/', '/about'],
            'trailing slash stored, bare requested' => ['/about/', 'about'],
            'both slashes stored, bare requested' => ['/about/', 'about/'],
            'nested path, mismatched slashes' => ['blog/hello/', '/blog/hello'],
        ];
    }

    /**
     * @dataProvider equivalentSpellings
     */
    public function test_it_matches_a_rule_however_the_path_was_spelled(string $stored, string $requested): void
    {
        SeoRedirect::create(['from' => $stored, 'to' => '/new', 'status' => 301]);

        $rule = $this->repository()->find($requested);

        self::assertNotNull($rule, "Stored '{$stored}' should be reachable via '{$requested}'.");
        self::assertSame('/new', $rule['to']);
        self::assertSame(301, $rule['status']);
    }

    public function test_it_returns_null_for_an_unknown_path(): void
    {
        SeoRedirect::create(['from' => '/about', 'to' => '/new', 'status' => 301]);

        self::assertNull($this->repository()->find('/nothing-here'));
    }

    public function test_it_distinguishes_paths_that_merely_share_a_prefix(): void
    {
        SeoRedirect::create(['from' => '/about', 'to' => '/a', 'status' => 301]);
        SeoRedirect::create(['from' => '/about-us', 'to' => '/b', 'status' => 301]);

        self::assertSame('/a', $this->repository()->find('/about')['to']);
        self::assertSame('/b', $this->repository()->find('/about-us')['to']);
    }

    public function test_it_preserves_the_root_path(): void
    {
        SeoRedirect::create(['from' => '/', 'to' => '/home', 'status' => 302]);

        self::assertSame('/home', $this->repository()->find('/')['to']);
    }

    public function test_it_carries_the_stored_spelling_so_hits_can_be_recorded(): void
    {
        SeoRedirect::create(['from' => 'about/', 'to' => '/new', 'status' => 301]);

        self::assertSame('about/', $this->repository()->find('/about')['from']);
    }

    public function test_it_records_a_hit_against_an_unnormalised_row(): void
    {
        // The WHERE clause used to be built from the normalised path, so a row
        // stored as `about/` was never matched and its counter stayed at zero.
        SeoRedirect::create(['from' => 'about/', 'to' => '/new', 'status' => 301, 'hits' => 0]);

        $this->repository()->recordHit('/about');

        $row = SeoRedirect::query()->firstOrFail();
        self::assertSame(1, $row->hits);
        self::assertNotNull($row->last_hit_at);
    }

    public function test_recording_a_hit_for_an_unknown_path_is_a_no_op(): void
    {
        SeoRedirect::create(['from' => '/about', 'to' => '/new', 'status' => 301, 'hits' => 0]);

        $this->repository()->recordHit('/nothing-here');

        self::assertSame(0, SeoRedirect::query()->firstOrFail()->hits);
    }

    public function test_hits_accumulate_across_requests(): void
    {
        SeoRedirect::create(['from' => '/about', 'to' => '/new', 'status' => 301, 'hits' => 0]);

        $this->repository()->recordHit('/about');
        $this->repository()->recordHit('/about');
        $this->repository()->recordHit('/about');

        self::assertSame(3, SeoRedirect::query()->firstOrFail()->hits);
    }

    public function test_saving_a_rule_busts_the_cached_map(): void
    {
        $repository = $this->repository();

        self::assertNull($repository->find('/about'));

        SeoRedirect::create(['from' => '/about', 'to' => '/new', 'status' => 301]);

        self::assertNotNull($repository->find('/about'), 'The model observer should have flushed the cache.');
    }

    public function test_deleting_a_rule_busts_the_cached_map(): void
    {
        $rule = SeoRedirect::create(['from' => '/about', 'to' => '/new', 'status' => 301]);
        $repository = $this->repository();

        self::assertNotNull($repository->find('/about'));

        $rule->delete();

        self::assertNull($repository->find('/about'));
    }

    public function test_it_survives_a_missing_table(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->drop('seo_redirects');

        // Installing the package before running its migrations must not fatal.
        self::assertNull($this->repository()->find('/about'));
    }
}
