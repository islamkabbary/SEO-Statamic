<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SilaSeo\Laravel\Concerns\HasSeo;
use SilaSeo\Laravel\Contracts\SeoSource;
use SilaSeo\Laravel\Facades\Seo;

final class HasSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('seo_test_pages')) {
            Schema::create('seo_test_pages', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('summary')->nullable();
            });
        }
    }

    public function testStoredMetaOverridesModelDefaults(): void
    {
        $page = SeoTestPage::create(['name' => 'My Course', 'summary' => 'Learn things']);
        $page->seoMeta()->create(['locale' => 'en', 'payload' => ['title' => 'Custom SEO Title']]);

        $payload = $page->fresh()->toSeoPayload('en');

        self::assertSame('Custom SEO Title', $payload['title']);
        self::assertSame('Learn things', $payload['description']);
    }

    public function testModelDefaultsUsedWhenNoStoredMeta(): void
    {
        $page = SeoTestPage::create(['name' => 'Plain Page', 'summary' => 'A summary']);

        $head = Seo::for($page->fresh())->forUrl('https://example.com/plain')->forLocale('en')->head();

        self::assertStringContainsString('<title>Plain Page - Sila</title>', $head);
        self::assertStringContainsString('<meta name="description" content="A summary">', $head);
    }
}

/**
 * @property string $name
 * @property string|null $summary
 */
class SeoTestPage extends Model implements SeoSource
{
    use HasSeo;

    protected $table = 'seo_test_pages';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * @return array<string,mixed>
     */
    protected function defaultSeoPayload(string $locale): array
    {
        return [
            'title' => $this->name,
            'description' => $this->summary,
        ];
    }
}