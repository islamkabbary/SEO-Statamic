<?php

declare(strict_types=1);

namespace SilaSeo\Core\Sitemap;

/**
 * Renders a list of {@see SitemapUrl}s into a sitemaps.org XML document with
 * the xhtml (hreflang) and image extensions.
 */
final class SitemapRenderer
{
    private const HEADER = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * @param list<SitemapUrl> $urls
     */
    public function render(array $urls): string
    {
        $urls = $this->dedupeByLoc($urls);

        $lines = [
            self::HEADER,
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
                . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
                . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . $this->escape($url->loc) . '</loc>';

            if ($url->lastmod !== null && $url->lastmod !== '') {
                $lines[] = '    <lastmod>' . $this->escape($url->lastmod) . '</lastmod>';
            }

            foreach ($url->alternates as $hreflang => $href) {
                $lines[] = '    <xhtml:link rel="alternate" hreflang="' . $this->escape((string) $hreflang)
                    . '" href="' . $this->escape($href) . '"/>';
            }

            foreach ($url->images as $image) {
                $lines[] = '    <image:image><image:loc>' . $this->escape($image) . '</image:loc></image:image>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Collapse repeated locations (keeping the first), so a source that emits the
     * same URL twice — e.g. unlinked localizations resolving to one path — never
     * produces duplicate <url> entries.
     *
     * @param list<SitemapUrl> $urls
     *
     * @return list<SitemapUrl>
     */
    private function dedupeByLoc(array $urls): array
    {
        $seen = [];
        $unique = [];

        foreach ($urls as $url) {
            if (isset($seen[$url->loc])) {
                continue;
            }

            $seen[$url->loc] = true;
            $unique[] = $url;
        }

        return $unique;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}