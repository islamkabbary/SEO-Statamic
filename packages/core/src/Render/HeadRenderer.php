<?php

declare(strict_types=1);

namespace SilaSeo\Core\Render;

use SilaSeo\Core\Context\SeoContext;
use SilaSeo\Core\Meta\MetaBuilder;
use SilaSeo\Core\Meta\MetaTag;
use SilaSeo\Core\Schema\Graph;

/**
 * The single entry point that turns a {@see SeoContext} into rendered
 * {@see SeoResult} output. Every host bridge ultimately calls this, so the
 * emitted bytes are identical regardless of platform or rendering engine.
 */
final class HeadRenderer
{
    public function __construct(
        private readonly MetaBuilder $metaBuilder = new MetaBuilder(),
    ) {
    }

    public function render(SeoContext $context): SeoResult
    {
        $metaHtml = implode("\n", array_map(
            static fn (MetaTag $tag): string => $tag->render(),
            $this->metaBuilder->build($context),
        ));

        $graph = Graph::fromNodes($context->schema);
        $jsonLd = $graph->isEmpty() ? '' : $graph->render();

        $headHtml = $jsonLd === ''
            ? $metaHtml
            : $metaHtml . "\n" . $this->scriptTag($jsonLd);

        return new SeoResult($headHtml, $jsonLd, $this->httpHeaders($context));
    }

    private function scriptTag(string $jsonLd): string
    {
        return '<script type="application/ld+json">' . $jsonLd . '</script>';
    }

    /**
     * @return array<string,string>
     */
    private function httpHeaders(SeoContext $context): array
    {
        if ($context->robots === []) {
            return [];
        }

        return ['X-Robots-Tag' => implode(', ', $context->robots)];
    }
}