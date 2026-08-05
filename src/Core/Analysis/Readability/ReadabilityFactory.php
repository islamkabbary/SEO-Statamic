<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Readability;

use SilaSeo\Core\Analysis\AnalysisConfig;

/**
 * Picks the readability strategy for a locale: Arabic for the "ar" primary
 * subtag, English (Flesch) otherwise.
 */
final class ReadabilityFactory
{
    public function for(string $locale, AnalysisConfig $config): ReadabilityAnalyzer
    {
        $primary = strtolower(explode('-', str_replace('_', '-', $locale))[0]);

        return $primary === 'ar'
            ? new ArabicReadability($config->arabicWordWeight, $config->arabicSentenceWeight)
            : new EnglishReadability();
    }
}