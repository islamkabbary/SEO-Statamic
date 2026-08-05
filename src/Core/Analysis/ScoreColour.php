<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

/**
 * The traffic-light colour for an overall or per-dimension score.
 * Bands: Red 0-49, Orange 50-79, Green 80-100.
 */
enum ScoreColour: string
{
    case Red = 'red';
    case Orange = 'orange';
    case Green = 'green';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::Green,
            $score >= 50 => self::Orange,
            default => self::Red,
        };
    }

    /**
     * Cap a colour so it can never be greener than the given ceiling
     * (used by the must-gate to hold the score at Orange).
     */
    public function capAt(self $ceiling): self
    {
        $rank = [self::Red->value => 0, self::Orange->value => 1, self::Green->value => 2];

        return $rank[$this->value] > $rank[$ceiling->value] ? $ceiling : $this;
    }
}