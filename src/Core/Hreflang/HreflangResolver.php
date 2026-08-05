<?php

declare(strict_types=1);

namespace SilaSeo\Core\Hreflang;

use SilaSeo\Core\Meta\MetaTag;

/**
 * Turns a list of {@see Alternate}s into rel="alternate" hreflang <link> tags,
 * guaranteeing an x-default entry so search engines always have a fallback.
 */
final class HreflangResolver
{
    /**
     * @param list<Alternate> $alternates
     *
     * @return list<MetaTag>
     */
    public function resolve(array $alternates): array
    {
        if ($alternates === []) {
            return [];
        }

        $alternates = $this->withXDefault($alternates);

        $tags = [];

        foreach ($alternates as $alternate) {
            $tags[] = MetaTag::link('alternate', $alternate->url, [
                'hreflang' => $alternate->hreflang,
            ]);
        }

        return $tags;
    }

    /**
     * Append an x-default pointing at the first alternate when none is supplied.
     *
     * @param list<Alternate> $alternates
     *
     * @return list<Alternate>
     */
    private function withXDefault(array $alternates): array
    {
        foreach ($alternates as $alternate) {
            if ($alternate->isXDefault()) {
                return $alternates;
            }
        }

        $alternates[] = new Alternate('x-default', $alternates[0]->url);

        return $alternates;
    }
}