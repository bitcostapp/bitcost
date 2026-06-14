<?php

namespace App\Filament\Pages;

use App\Enums\TaskProvider;
use App\Models\WorkProviderConnection;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WorkProviders extends Page
{
    protected string $view = 'filament.pages.work-providers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $title = 'Work Providers';

    protected static ?string $navigationLabel = 'Work Providers';

    /**
     * The external Work Providers a Task may link to. "Internal" is not a Work
     * Provider, so it is excluded here.
     *
     * @return array<int, TaskProvider>
     */
    protected function providers(): array
    {
        return [TaskProvider::Github, TaskProvider::Jira];
    }

    /**
     * View data: each Work Provider with its presentation and connection state.
     *
     * @return array<int, array{value: string, label: string, description: string, connected: bool, connectedAt: string|null}>
     */
    public function getProviders(): array
    {
        $connections = WorkProviderConnection::query()
            ->whereNotNull('connected_at')
            ->pluck('connected_at', 'provider');

        return collect($this->providers())
            ->map(fn (TaskProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
                'description' => $this->description($provider),
                'connected' => $connections->has($provider->value),
                'connectedAt' => $connections->get($provider->value)?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Short marketing-style blurb shown on each provider card.
     */
    private function description(TaskProvider $provider): string
    {
        return match ($provider) {
            TaskProvider::Github => 'Import issues from a repository as Tasks and track their AI spend.',
            TaskProvider::Jira => 'Link Jira tickets to Tasks for cost attribution.',
            default => '',
        };
    }

    /**
     * Mark a Work Provider as connected (placeholder — no real OAuth yet).
     */
    public function connect(string $provider): void
    {
        $this->assertProvider($provider);

        WorkProviderConnection::query()->updateOrCreate(
            ['provider' => $provider],
            ['connected_at' => now()],
        );

        Notification::make()
            ->title(TaskProvider::from($provider)->label().' connected')
            ->success()
            ->send();
    }

    /**
     * Disconnect a Work Provider (clears the connection timestamp).
     */
    public function disconnect(string $provider): void
    {
        $this->assertProvider($provider);

        WorkProviderConnection::query()
            ->where('provider', $provider)
            ->update(['connected_at' => null]);

        Notification::make()
            ->title(TaskProvider::from($provider)->label().' disconnected')
            ->send();
    }

    /**
     * Guard against toggling anything that is not a real Work Provider.
     */
    private function assertProvider(string $provider): void
    {
        abort_unless(
            in_array(TaskProvider::tryFrom($provider), $this->providers(), true),
            404,
        );
    }
}
