<?php

declare(strict_types=1);

namespace SilaSeo\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SilaSeo\Laravel\MetaService;

/**
 * @method static MetaService for(mixed $source)
 * @method static MetaService defaults(array $payload)
 * @method static MetaService title(string $title)
 * @method static MetaService description(string $description)
 * @method static MetaService image(string $image)
 * @method static MetaService canonical(string $canonical)
 * @method static MetaService robots(array|string $robots)
 * @method static MetaService noindex(bool $follow = true)
 * @method static MetaService alternate(string $hreflang, string $url)
 * @method static MetaService schema(array $node)
 * @method static \SilaSeo\Core\Render\SeoResult render()
 * @method static string head()
 * @method static string jsonLd()
 * @method static array httpHeaders()
 *
 * @see MetaService
 */
final class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MetaService::class;
    }
}