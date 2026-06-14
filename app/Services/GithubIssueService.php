<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Reads issues from the fixed GitHub repository (configured via
 * `services.github.tasks_repo`) using the local `gh` CLI. Each open issue is a
 * candidate to import as a Bitcost Task.
 */
class GithubIssueService
{
    /**
     * The configured `owner/name` repository, or null when unset.
     */
    public function repo(): ?string
    {
        $repo = config('services.github.tasks_repo');

        return is_string($repo) && $repo !== '' ? $repo : null;
    }

    /**
     * Whether a target repository is configured.
     */
    public function isConfigured(): bool
    {
        return $this->repo() !== null;
    }

    /**
     * Path to the `gh` binary (absolute under FPM, where PATH is minimal).
     */
    private function binary(): string
    {
        $path = config('services.github.gh_path');

        return is_string($path) && $path !== '' ? $path : 'gh';
    }

    /**
     * Environment for the `gh` process: a PATH that includes the common
     * Homebrew locations, plus GH_TOKEN when configured so auth does not rely
     * on the keyring (unreachable from a background FPM process).
     *
     * @return array<string, string>
     */
    private function processEnv(): array
    {
        $path = implode(':', array_filter([
            '/opt/homebrew/bin',
            '/usr/local/bin',
            getenv('PATH') ?: '/usr/bin:/bin',
        ]));

        $env = ['PATH' => $path];

        $token = config('services.github.token');

        if (is_string($token) && $token !== '') {
            $env['GH_TOKEN'] = $token;
        }

        return $env;
    }

    /**
     * Fetch open issues from the configured repository.
     *
     * @return array<int, array{number: int, title: string, url: string, state: string}>
     *
     * @throws RuntimeException when no repo is configured or the `gh` CLI fails.
     */
    public function openIssues(int $limit = 100): array
    {
        $repo = $this->repo();

        if ($repo === null) {
            throw new RuntimeException('No GitHub repository is configured (set GITHUB_TASKS_REPO).');
        }

        $result = Process::env($this->processEnv())->run([
            $this->binary(), 'issue', 'list',
            '--repo', $repo,
            '--state', 'open',
            '--json', 'number,title,url,state',
            '--limit', (string) $limit,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'The `gh` CLI failed to list issues.');
        }

        /** @var array<int, array<string, mixed>> $issues */
        $issues = json_decode($result->output(), true) ?: [];

        return array_map(fn (array $issue): array => [
            'number' => (int) $issue['number'],
            'title' => (string) $issue['title'],
            'url' => (string) $issue['url'],
            'state' => (string) $issue['state'],
        ], $issues);
    }
}
