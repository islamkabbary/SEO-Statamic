<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/BlogPosting — an Article published as a blog post.
 */
final class BlogPosting extends Article
{
    public function type(): string
    {
        return 'BlogPosting';
    }
}