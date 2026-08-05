<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

use SilaSeo\Core\Analysis\Readability\ReadabilityFactory;
use SilaSeo\Core\Analysis\Readability\ReadabilityResult;
use SilaSeo\Core\Analysis\Text\ContentParser;
use SilaSeo\Core\Analysis\Text\ExtractedContent;
use SilaSeo\Core\Analysis\Text\KeywordMatcher;
use SilaSeo\Core\Analysis\Text\RegexContentParser;
use SilaSeo\Core\Analysis\Text\Tokenizer;

/**
 * The single, immutable parse of a page, built ONCE up front. Every check reads
 * from here and must never re-parse, re-tokenize, or re-normalize. This is the
 * structural guarantee of the "parse once, never on the render path" cost model.
 */
final class TextStatistics
{
    private function __construct(
        public readonly bool $isArabic,
        public readonly ExtractedContent $content,
        public readonly int $wordCount,
        public readonly int $sentenceCount,
        public readonly int $bodyKeywordCount,
        public readonly float $keywordDensity,
        public readonly bool $keywordInFirstParagraph,
        public readonly ReadabilityResult $readability,
        public readonly KeywordMatcher $matcher,
    ) {
    }

    public static function build(
        AnalysisInput $input,
        AnalysisConfig $config,
        ?ContentParser $parser = null,
        ?ReadabilityFactory $readabilityFactory = null,
    ): self {
        $parser ??= new RegexContentParser();
        $readabilityFactory ??= new ReadabilityFactory();

        $isArabic = $input->isArabic();
        $matcher = new KeywordMatcher($isArabic);
        $content = $parser->parse($input->body, $input->siteHost);

        $words = Tokenizer::words($content->plainText);
        $wordCount = count($words);
        $sentenceCount = count(Tokenizer::sentences($content->plainText));

        $keyword = trim($input->focusKeyword);
        $bodyKeywordCount = $keyword === '' ? 0 : $matcher->count($content->plainText, $keyword);
        $density = ($keyword === '' || $wordCount === 0) ? 0.0 : ($bodyKeywordCount / $wordCount) * 100;
        $inFirstParagraph = $keyword !== '' && $matcher->contains($content->firstParagraph, $keyword);

        $readability = ($wordCount > 0 && $sentenceCount > 0)
            ? $readabilityFactory->for($input->locale, $config)->analyze($words, $sentenceCount)
            : ReadabilityResult::unknown();

        return new self(
            $isArabic,
            $content,
            $wordCount,
            $sentenceCount,
            $bodyKeywordCount,
            $density,
            $inFirstParagraph,
            $readability,
            $matcher,
        );
    }
}