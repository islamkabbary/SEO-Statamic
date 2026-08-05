<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * How important a check is. A failing `Must` check triggers the soft gate that
 * caps the overall colour at Orange (you can't be green with a missing
 * fundamental like an empty title or keyword).
 */
enum Priority: string
{
    case Must = 'must';
    case Should = 'should';
    case Nice = 'nice';
}