<?php

namespace App\Filament\Pages;

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;
use App\Models\WorkProviderConnection;
use App\Services\GithubIssueService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ImportGithubTasks extends Page
{
    protected string $view = 'filament.pages.import-github-tasks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $title = 'Import from GitHub';

    protected static ?string $navigationLabel = 'Import from GitHub';

    public ?int $departmentId = null;

    /**
     * Issue number => whether it is checked for import.
     *
     * @var array<int, bool>
     */
    public array $selected = [];

    /**
     * The open issues fetched from the configured repository.
     *
     * @var array<int, array{number: int, title: string, url: string, state: string}>
     */
    public array $issues = [];

    public ?string $loadError = null;

    /**
     * Only surface this page once GitHub is connected.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return self::githubConnected();
    }

    public static function canAccess(): bool
    {
        return self::githubConnected();
    }

    public function mount(): void
    {
        $this->departmentId = Team::query()
            ->where('is_personal', false)
            ->orderBy('name')
            ->value('id');

        $this->loadIssues();
    }

    /**
     * (Re)load the open issues from the configured repository.
     */
    public function loadIssues(): void
    {
        try {
            $this->issues = app(GithubIssueService::class)->openIssues();
            $this->loadError = null;
        } catch (Throwable $exception) {
            $this->issues = [];
            $this->loadError = $exception->getMessage();
        }
    }

    /**
     * Import the checked issues as Tasks in the selected Department.
     */
    public function import(): void
    {
        if ($this->departmentId === null) {
            Notification::make()->title('Choose a department first')->warning()->send();

            return;
        }

        $byNumber = collect($this->issues)->keyBy('number');

        $selectedNumbers = collect($this->selected)
            ->filter()
            ->keys()
            ->map(fn ($number): int => (int) $number);

        if ($selectedNumbers->isEmpty()) {
            Notification::make()->title('Select at least one issue')->warning()->send();

            return;
        }

        $created = 0;

        foreach ($selectedNumbers as $number) {
            $issue = $byNumber->get($number);

            if ($issue === null) {
                continue;
            }

            $task = Task::firstOrCreate(
                [
                    'team_id' => $this->departmentId,
                    'external_url' => $issue['url'],
                ],
                [
                    'name' => $issue['title'],
                    'external_provider' => TaskProvider::Github->value,
                    'status' => TaskStatus::Open,
                    'user_id' => Auth::id(),
                ],
            );

            if ($task->wasRecentlyCreated) {
                $created++;
            }
        }

        $skipped = $selectedNumbers->count() - $created;

        Notification::make()
            ->title("Imported {$created} task(s)".($skipped > 0 ? ", {$skipped} already existed" : ''))
            ->success()
            ->send();

        $this->selected = [];
    }

    /**
     * Department options for the import target select.
     *
     * @return array<int, string>
     */
    public function departmentOptions(): array
    {
        return Team::query()
            ->where('is_personal', false)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function repo(): ?string
    {
        return app(GithubIssueService::class)->repo();
    }

    /**
     * Whether the GitHub Work Provider is currently connected.
     */
    protected static function githubConnected(): bool
    {
        return WorkProviderConnection::query()
            ->where('provider', TaskProvider::Github->value)
            ->whereNotNull('connected_at')
            ->exists();
    }
}
