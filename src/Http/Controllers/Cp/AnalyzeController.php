<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Http\Controllers\Cp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SilaSeo\Core\Analysis\ContentAnalyzer;
use SilaSeo\Statamic\Analysis\EntryAnalysisFactory;
use SilaSeo\Statamic\Analysis\ReportPresenter;
use Statamic\Http\Controllers\CP\CpController;

/**
 * Live SEO analysis endpoint for the Control Panel. Receives the current
 * publish-form values and returns the report so the panel updates as the
 * editor types. CP-authenticated (extends CpController); never hit publicly.
 */
class AnalyzeController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'values' => ['nullable', 'array'],
            'locale' => ['nullable', 'string'],
            'page_type' => ['nullable', 'string'],
        ]);

        $input = (new EntryAnalysisFactory())->fromValues(
            $data['values'] ?? [],
            $data['locale'] ?? 'en',
            $data['page_type'] ?? 'article',
        );

        $report = (new ContentAnalyzer())->analyze($input);

        return response()->json((new ReportPresenter())->present($report, $input->locale));
    }
}