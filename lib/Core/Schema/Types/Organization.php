<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/Organization — the publishing entity behind the site.
 */
class Organization extends AbstractSchemaType
{
    public function __construct(string $name, string $url)
    {
        $this->set('name', $name)->set('url', $url);
    }

    public function type(): string
    {
        return 'Organization';
    }

    public function logo(?string $url): static
    {
        return $this->set('logo', $url);
    }

    /**
     * @param list<string> $urls Social/profile URLs (sameAs).
     */
    public function sameAs(array $urls): static
    {
        return $this->set('sameAs', array_values(array_filter($urls)));
    }

    public function telephone(?string $telephone): static
    {
        return $this->set('telephone', $telephone);
    }

    public function email(?string $email): static
    {
        return $this->set('email', $email);
    }
}