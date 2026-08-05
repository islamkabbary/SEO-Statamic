<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/VideoObject.
 */
final class VideoObject extends AbstractSchemaType
{
    public function __construct(string $name)
    {
        $this->set('name', $name);
    }

    public function type(): string
    {
        return 'VideoObject';
    }

    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    public function thumbnailUrl(?string $url): static
    {
        return $this->set('thumbnailUrl', $url);
    }

    public function uploadDate(?string $date): static
    {
        return $this->set('uploadDate', $date);
    }

    public function contentUrl(?string $url): static
    {
        return $this->set('contentUrl', $url);
    }

    public function embedUrl(?string $url): static
    {
        return $this->set('embedUrl', $url);
    }

    /**
     * ISO 8601 duration, e.g. "PT1M33S".
     */
    public function duration(?string $duration): static
    {
        return $this->set('duration', $duration);
    }
}