<?php

declare(strict_types=1);

namespace SilaSeo\Core\Analysis;

use SilaSeo\Core\Analysis\Checks\ContentChecks;
use SilaSeo\Core\Analysis\Checks\DescriptionChecks;
use SilaSeo\Core\Analysis\Checks\Evaluator;
use SilaSeo\Core\Analysis\Checks\ImageChecks;
use SilaSeo\Core\Analysis\Checks\KeywordChecks;
use SilaSeo\Core\Analysis\Checks\LinkChecks;
use SilaSeo\Core\Analysis\Checks\TitleChecks;
use SilaSeo\Core\Analysis\Checks\UrlChecks;

/**
 * Entry point for content analysis: builds the single {@see TextStatistics},
 * runs every check evaluator, drops disabled checks, and scores the result.
 *
 * IMPORTANT: invoke this on the CMS save lifecycle only — never on the public
 * render path.
 */
final class ContentAnalyzer
{
    /** @var list<Evaluator> */
    private array $evaluators;

    private Scorer $scorer;

    /**
     * @param list<Evaluator>|null $evaluators
     */
    public function __construct(?array $evaluators = null, ?Scorer $scorer = null)
    {
        $this->evaluators = $evaluators ?? self::defaultEvaluators();
        $this->scorer = $scorer ?? new Scorer();
    }

    public function analyze(AnalysisInput $input, ?AnalysisConfig $config = null): AnalysisReport
    {
        $config ??= AnalysisConfig::default();
        $stats = TextStatistics::build($input, $config);
        $context = new AnalysisContext($input, $stats, $config);

        $results = [];

        foreach ($this->evaluators as $evaluator) {
            foreach ($evaluator->run($context) as $result) {
                if (! $config->isDisabled($result->id)) {
                    $results[] = $result;
                }
            }
        }

        return $this->scorer->score($results, $config);
    }

    /**
     * @return list<Evaluator>
     */
    private static function defaultEvaluators(): array
    {
        return [
            new KeywordChecks(),
            new TitleChecks(),
            new DescriptionChecks(),
            new UrlChecks(),
            new ContentChecks(),
            new ImageChecks(),
            new LinkChecks(),
        ];
    }
}