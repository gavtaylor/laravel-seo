<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow\Console;

use GavTaylor\Seo\IndexNow\ContentHasher;
use GavTaylor\Seo\IndexNow\Contracts\UrlProvider;
use GavTaylor\Seo\IndexNow\IndexNowClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class SubmitCommand extends Command
{
    protected $signature = 'indexnow:submit
        {--all : Resubmit every page, ignoring stored hashes}
        {--dry-run : List the pages that would be submitted without contacting anyone}
        {--url=* : Check only these URLs instead of the whole site}';

    protected $description = 'Submit pages whose rendered content changed to IndexNow search engines';

    public function handle(UrlProvider $provider, ContentHasher $hasher, IndexNowClient $client): int
    {
        $enabled = config('seo.indexnow.enabled');

        if (! ($enabled ?? app()->isProduction())) {
            $this->components->warn('IndexNow is disabled in this environment (set INDEXNOW_ENABLED=true to enable).');

            return self::SUCCESS;
        }

        if (! is_string(config('seo.indexnow.key')) || config('seo.indexnow.key') === '') {
            $this->components->error('No IndexNow key is configured (INDEXNOW_KEY).');

            return self::FAILURE;
        }

        /** @var list<string> $urls */
        $urls = $this->option('url') !== [] ? array_values((array) $this->option('url')) : $provider->urls();
        $urls = array_values(array_filter($urls, fn (string $url): bool => ! Str::is((array) config('seo.indexnow.exclude', []), $url)));

        $changed = [];

        foreach ($urls as $url) {
            $hash = $this->fetchHash($url, $hasher);

            if ($hash === null) {
                continue;
            }

            if ($this->option('all') || $this->storedHash($url) !== $hash) {
                $changed[$url] = $hash;
            }
        }

        if ($changed === []) {
            $this->components->info('No changed pages to submit ('.count($urls).' checked).');

            return self::SUCCESS;
        }

        foreach (array_keys($changed) as $url) {
            $this->line(($this->option('dry-run') ? 'Would submit ' : 'Submitting ').$url);
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach (array_chunk($changed, IndexNowClient::MAX_URLS_PER_REQUEST, true) as $chunk) {
            $status = $client->submit(array_keys($chunk));

            if ($status !== 200 && $status !== 202) {
                $this->components->error("IndexNow rejected the submission (HTTP {$status}); it will be retried next run.");

                return self::FAILURE;
            }

            foreach ($chunk as $url => $hash) {
                $this->remember($url, $hash);
            }
        }

        $this->components->info('Submitted '.count($changed).' changed page(s) to IndexNow.');

        return self::SUCCESS;
    }

    private function fetchHash(string $url, ContentHasher $hasher): ?string
    {
        try {
            $response = Http::timeout((int) config('seo.indexnow.timeout', 15))->withoutRedirecting()->get($url);
        } catch (Throwable $e) {
            $this->components->warn("Skipped {$url}: {$e->getMessage()}");

            return null;
        }

        if ($response->status() !== 200) {
            $this->components->warn("Skipped {$url} (HTTP {$response->status()}).");

            return null;
        }

        return $hasher->hash($response->body());
    }

    private function storedHash(string $url): ?string
    {
        /** @var string|null $hash */
        $hash = DB::table('indexnow_urls')->where('url_hash', hash('sha256', $url))->value('content_hash');

        return $hash;
    }

    private function remember(string $url, string $hash): void
    {
        DB::table('indexnow_urls')->updateOrInsert(
            ['url_hash' => hash('sha256', $url)],
            ['url' => $url, 'content_hash' => $hash, 'submitted_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
