<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * The category a check belongs to, used to group per-dimension subscores in the
 * report so editors see where they lost points.
 */
enum Dimension: string
{
    case Keyword = 'keyword';
    case Title = 'title';
    case Description = 'description';
    case Content = 'content';
    case Links = 'links';
    case Images = 'images';
    case Technical = 'technical';
}