<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\Redirects\RedirectCsv;

final class RedirectCsvTest extends TestCase
{
    private RedirectCsv $csv;

    protected function setUp(): void
    {
        $this->csv = new RedirectCsv();
    }

    public function testExportWritesHeaderAndRows(): void
    {
        $out = $this->csv->export([
            ['from' => '/old', 'to' => '/new', 'status' => 301],
            ['from' => '/gone', 'to' => '', 'status' => 410],
        ]);

        $lines = array_values(array_filter(explode("\n", trim($out))));
        self::assertSame('from,to,status', trim($lines[0]));
        self::assertStringContainsString('/old,/new,301', $out);
        self::assertStringContainsString('/gone,,410', $out);
    }

    public function testImportSkipsHeaderAndParsesRows(): void
    {
        $rows = $this->csv->import("from,to,status\n/a,/b,301\n/c,/d,302");

        self::assertCount(2, $rows);
        self::assertSame(['from' => '/a', 'to' => '/b', 'status' => 301], $rows[0]);
        self::assertSame(302, $rows[1]['status']);
    }

    public function testImportDefaultsAndValidatesStatus(): void
    {
        $rows = $this->csv->import("/a,/b\n/c,/d,999");

        self::assertSame(301, $rows[0]['status']);
        self::assertSame(301, $rows[1]['status']);
    }

    public function testImportKeeps410WithoutDestinationButDropsOtherEmptyTargets(): void
    {
        $rows = $this->csv->import("/gone,,410\n/bad,,302\n,,301");

        self::assertCount(1, $rows);
        self::assertSame(['from' => '/gone', 'to' => '', 'status' => 410], $rows[0]);
    }

    public function testImportToleratesBomBlankLinesAndCrlf(): void
    {
        $rows = $this->csv->import("\xEF\xBB\xBFfrom,to,status\r\n\r\n/a,/b,301\r\n");

        self::assertCount(1, $rows);
        self::assertSame('/a', $rows[0]['from']);
    }

    public function testExportImportRoundTrip(): void
    {
        $original = [
            ['from' => '/x', 'to' => '/y', 'status' => 308],
            ['from' => '/z', 'to' => '', 'status' => 410],
        ];

        self::assertSame($original, $this->csv->import($this->csv->export($original)));
    }
}