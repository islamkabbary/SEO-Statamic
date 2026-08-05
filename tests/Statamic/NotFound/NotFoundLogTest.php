<?php

declare(strict_types=1);

namespace SilaSeo\Statamic\Tests\NotFound;

use PHPUnit\Framework\TestCase;
use SilaSeo\Statamic\NotFound\NotFoundLog;

final class NotFoundLogTest extends TestCase
{
    private string $directory;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/silaseo-404-' . bin2hex(random_bytes(6));
        $this->file = $this->directory . '/404_log.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            @unlink($this->file);
        }

        if (is_dir($this->directory)) {
            @rmdir($this->directory);
        }

        parent::tearDown();
    }

    private function log(): NotFoundLog
    {
        return new NotFoundLog($this->file);
    }

    /**
     * @param array<string, array{hits: int, last_seen: string}> $entries
     */
    private function seed(array $entries): void
    {
        @mkdir($this->directory, 0o755, true);
        file_put_contents($this->file, json_encode($entries));
    }

    public function test_it_creates_the_log_directory_on_first_write(): void
    {
        self::assertDirectoryDoesNotExist($this->directory);

        $this->log()->record('/missing-page');

        self::assertFileExists($this->file);
    }

    public function test_it_counts_repeat_misses(): void
    {
        $log = $this->log();

        $log->record('/missing-page');
        $log->record('/missing-page');
        $log->record('/missing-page');

        $entries = $log->entries();

        self::assertCount(1, $entries);
        self::assertSame('/missing-page', $entries[0]['path']);
        self::assertSame(3, $entries[0]['hits']);
        self::assertNotNull($entries[0]['last_seen']);
    }

    public function test_it_orders_entries_by_hit_count(): void
    {
        $log = $this->log();

        $log->record('/rare');
        foreach (range(1, 5) as $ignored) {
            $log->record('/common');
        }
        $log->record('/rare');

        $entries = $log->entries();

        self::assertSame('/common', $entries[0]['path']);
        self::assertSame('/rare', $entries[1]['path']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scannerProbes(): array
    {
        return [
            'wordpress admin' => ['/wp-admin/install.php'],
            'wordpress content' => ['/wp-content/uploads/x.txt'],
            'wordpress login' => ['/wp-login'],
            'php file' => ['/index.php'],
            'php in a subpath' => ['/a/b/shell.phtml'],
            'environment file' => ['/.env'],
            'nested environment file' => ['/config/.env'],
            'git directory' => ['/.git/config'],
            'sql dump' => ['/backup.sql'],
            'archive' => ['/site.zip'],
            'phpmyadmin' => ['/phpmyadmin/index'],
            'vendor path' => ['/vendor/autoload'],
            'xmlrpc' => ['/xmlrpc'],
        ];
    }

    /**
     * @dataProvider scannerProbes
     */
    public function test_it_ignores_scanner_noise(string $path): void
    {
        // These arrive in the hundreds per minute on a public site. Logging them
        // buries genuine broken links and consumes the entry cap.
        self::assertFalse($this->log()->shouldRecord($path));

        $this->log()->record($path);

        self::assertSame([], $this->log()->entries());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function genuineMisses(): array
    {
        return [
            'renamed page' => ['/about-us'],
            'nested entry' => ['/blog/how-to-learn-english'],
            'arabic slug' => ['/blog/ماذا-تعرف-عن-الأمن-السيبراني'],
            'locale prefixed' => ['/en/courses/ielts'],
            'trailing segment that merely contains php' => ['/blog/php-tutorials'],
            'root' => ['/'],
        ];
    }

    /**
     * @dataProvider genuineMisses
     */
    public function test_it_records_genuine_misses(string $path): void
    {
        self::assertTrue($this->log()->shouldRecord($path));

        $this->log()->record($path);

        self::assertSame([$path], array_column($this->log()->entries(), 'path'));
    }

    public function test_it_preserves_arabic_paths_readably(): void
    {
        $this->log()->record('/blog/الأمن-السيبراني');

        // JSON_UNESCAPED_UNICODE: the CP viewer and anyone opening the file by
        // hand should see the actual slug, not ال...
        self::assertStringContainsString('الأمن-السيبراني', (string) file_get_contents($this->file));
    }

    public function test_it_caps_the_number_of_entries(): void
    {
        $seed = [];
        foreach (range(1, NotFoundLog::MAX_ENTRIES + 200) as $i) {
            $seed["/probe-{$i}"] = ['hits' => 1, 'last_seen' => '2026-01-01 00:00:00'];
        }
        $seed['/genuinely-broken'] = ['hits' => 9999, 'last_seen' => '2026-01-01 00:00:00'];
        $this->seed($seed);

        $this->log()->record('/one-more');

        $entries = $this->log()->entries();

        self::assertCount(NotFoundLog::MAX_ENTRIES, $entries);
        // The cap must drop the long tail of one-off probes, not the real signal.
        self::assertSame('/genuinely-broken', $entries[0]['path']);
    }

    public function test_it_truncates_absurdly_long_paths(): void
    {
        $this->log()->record('/' . str_repeat('a', 5000));

        $entries = $this->log()->entries();

        self::assertCount(1, $entries);
        self::assertLessThanOrEqual(2048, mb_strlen($entries[0]['path']));
    }

    public function test_it_recovers_from_a_corrupt_log(): void
    {
        $this->seed([]);
        file_put_contents($this->file, '{ this is not json');

        $this->log()->record('/missing-page');

        $entries = $this->log()->entries();

        self::assertCount(1, $entries);
        self::assertSame('/missing-page', $entries[0]['path']);
    }

    public function test_it_recovers_from_an_empty_log(): void
    {
        $this->seed([]);
        file_put_contents($this->file, '');

        $this->log()->record('/missing-page');

        self::assertCount(1, $this->log()->entries());
    }

    public function test_it_ignores_non_object_rows(): void
    {
        $this->seed([]);
        file_put_contents($this->file, json_encode(['/a' => 'garbage', '/b' => ['hits' => 2]]));

        $entries = $this->log()->entries();

        self::assertSame([['path' => '/b', 'hits' => 2, 'last_seen' => '']], $entries);
    }

    public function test_reading_a_log_that_does_not_exist_yields_nothing(): void
    {
        self::assertSame([], $this->log()->entries());
    }

    public function test_it_leaves_the_file_valid_after_many_writes(): void
    {
        $log = $this->log();

        foreach (range(1, 50) as $i) {
            $log->record("/page-{$i}");
            $log->record('/page-1');
        }

        // Every write truncates and rewrites under an exclusive lock; a partial
        // write would leave undecodable JSON behind.
        $raw = (string) file_get_contents($this->file);
        self::assertIsArray(json_decode($raw, true), 'Log should always be valid JSON.');
        self::assertSame(50, count($log->entries()));
        self::assertSame(51, $log->entries()[0]['hits']);
    }

    public function test_clearing_removes_the_log(): void
    {
        $this->log()->record('/missing-page');
        self::assertFileExists($this->file);

        $this->log()->clear();

        self::assertFileDoesNotExist($this->file);
        self::assertSame([], $this->log()->entries());
    }

    public function test_clearing_a_missing_log_is_harmless(): void
    {
        $this->log()->clear();

        self::assertSame([], $this->log()->entries());
    }
}
