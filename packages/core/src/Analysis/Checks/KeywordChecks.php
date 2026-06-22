<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis\Checks;

use SilaSeo\Core\Analysis\AnalysisContext;
use SilaSeo\Core\Analysis\CheckResult;
use SilaSeo\Core\Analysis\CheckStatus;
use SilaSeo\Core\Analysis\Dimension;
use SilaSeo\Core\Analysis\Priority;
use SilaSeo\Core\Analysis\Text\Tokenizer;

final class KeywordChecks implements Evaluator
{
    public function run(AnalysisContext $context): array
    {
        return [
            $this->presence($context),
            $this->inTitle($context),
            $this->inDescription($context),
            $this->inUrl($context),
            $this->inFirstParagraph($context),
            $this->density($context),
            $this->inH1($context),
        ];
    }

    private function presence(AnalysisContext $context): CheckResult
    {
        $keyword = $context->keyword();
        $wordCount = count(Tokenizer::words($keyword));

        $status = match (true) {
            $keyword === '' => CheckStatus::Fail,
            $wordCount > 8 => CheckStatus::Warn,
            default => CheckStatus::Pass,
        };

        return CheckResult::make('focus_keyword_presence', Dimension::Keyword, Priority::Must, 10, $status);
    }

    private function inTitle(AnalysisContext $context): CheckResult
    {
        $status = $this->fieldStatus($context, $context->input->title);

        return CheckResult::make('title_keyword', Dimension::Keyword, Priority::Must, 14, $status);
    }

    private function inDescription(AnalysisContext $context): CheckResult
    {
        $status = $this->fieldStatus($context, $context->input->description);

        return CheckResult::make('description_keyword', Dimension::Keyword, Priority::Should, 9, $status);
    }

    private function inUrl(AnalysisContext $context): CheckResult
    {
        $slug = str_replace(['-', '_'], ' ', $context->input->slug);
        $status = $this->fieldStatus($context, $slug);

        return CheckResult::make('url_keyword', Dimension::Keyword, Priority::Should, 7, $status);
    }

    private function inFirstParagraph(AnalysisContext $context): CheckResult
    {
        if (! $context->hasKeyword() || ! $context->hasBody()) {
            return CheckResult::make('keyword_in_first_paragraph', Dimension::Keyword, Priority::Should, 10, CheckStatus::Skip);
        }

        $status = match (true) {
            $context->stats->keywordInFirstParagraph => CheckStatus::Pass,
            $context->stats->bodyKeywordCount > 0 => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return CheckResult::make('keyword_in_first_paragraph', Dimension::Keyword, Priority::Should, 10, $status);
    }

    private function density(AnalysisContext $context): CheckResult
    {
        if (! $context->hasKeyword() || ! $context->hasBody()) {
            return CheckResult::make('keyword_density', Dimension::Keyword, Priority::Should, 11, CheckStatus::Skip);
        }

        $density = $context->stats->keywordDensity;
        $config = $context->config;

        $status = match (true) {
            $density <= 0.0 => CheckStatus::Fail,
            $density < $config->densityMin => CheckStatus::Warn,
            $density <= $config->densityIdealMax => CheckStatus::Pass,
            $density <= $config->densityWarnMax => CheckStatus::Warn,
            default => CheckStatus::Fail,
        };

        return CheckResult::make('keyword_density', Dimension::Keyword, Priority::Should, 11, $status, [
            'density' => round($density, 2),
        ]);
    }

    private function inH1(AnalysisContext $context): CheckResult
    {
        if (! $context->hasKeyword() || ! $context->hasBody()) {
            return CheckResult::make('h1_keyword', Dimension::Keyword, Priority::Should, 8, CheckStatus::Skip);
        }

        $matcher = $context->stats->matcher;
        $keyword = $context->keyword();
        $found = false;

        foreach ($context->stats->content->headingsOfLevel(1) as $heading) {
            if ($matcher->contains($heading['text'], $keyword)) {
                $found = true;

                break;
            }
        }

        return CheckResult::make('h1_keyword', Dimension::Keyword, Priority::Should, 8, $found ? CheckStatus::Pass : CheckStatus::Fail);
    }

    private function fieldStatus(AnalysisContext $context, string $field): CheckStatus
    {
        if (! $context->hasKeyword()) {
            return CheckStatus::Skip;
        }

        return $context->stats->matcher->contains($field, $context->keyword())
            ? CheckStatus::Pass
            : CheckStatus::Fail;
    }
}