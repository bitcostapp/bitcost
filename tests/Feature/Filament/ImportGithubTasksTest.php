<?php

use App\Enums\TaskProvider;
use App\Filament\Pages\ImportGithubTasks;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkProviderConnection;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);

    config(['services.github.tasks_repo' => 'acme/widgets']);

    WorkProviderConnection::query()->create([
        'provider' => TaskProvider::Github->value,
        'connected_at' => now(),
    ]);

    $this->department = Team::factory()->create(['name' => 'Engineering', 'is_personal' => false]);

    $this->fakeIssues = json_encode([
        ['number' => 11, 'title' => 'Fix the webhook', 'url' => 'https://github.com/acme/widgets/issues/11', 'state' => 'OPEN'],
        ['number' => 12, 'title' => 'Add dark mode', 'url' => 'https://github.com/acme/widgets/issues/12', 'state' => 'OPEN'],
    ]);
});

it('lists open github issues from the configured repo', function () {
    Process::fake(['*' => Process::result(output: $this->fakeIssues)]);

    Livewire::test(ImportGithubTasks::class)
        ->assertSuccessful()
        ->assertSee('acme/widgets')
        ->assertSee('Fix the webhook')
        ->assertSee('Add dark mode');
});

it('imports selected issues as github tasks in a department', function () {
    Process::fake(['*' => Process::result(output: $this->fakeIssues)]);

    Livewire::test(ImportGithubTasks::class)
        ->set('departmentId', $this->department->id)
        ->set('selected', [11 => true, 12 => false])
        ->call('import');

    $task = Task::query()->where('external_url', 'https://github.com/acme/widgets/issues/11')->sole();

    expect($task->name)->toBe('Fix the webhook')
        ->and($task->team_id)->toBe($this->department->id)
        ->and($task->external_provider)->toBe('github')
        ->and($task->provider)->toBe(TaskProvider::Github)
        ->and($task->user_id)->toBe($this->admin->id);

    expect(Task::query()->where('external_url', 'https://github.com/acme/widgets/issues/12')->exists())->toBeFalse();
});

it('does not import the same issue twice', function () {
    Process::fake(['*' => Process::result(output: $this->fakeIssues)]);

    Livewire::test(ImportGithubTasks::class)
        ->set('departmentId', $this->department->id)
        ->set('selected', [11 => true])
        ->call('import')
        ->set('selected', [11 => true])
        ->call('import');

    expect(Task::query()->where('external_url', 'https://github.com/acme/widgets/issues/11')->count())->toBe(1);
});

it('shows an error when the gh cli fails', function () {
    Process::fake(['*' => Process::result(errorOutput: 'gh: not authenticated', exitCode: 1)]);

    Livewire::test(ImportGithubTasks::class)
        ->assertSet('loadError', 'gh: not authenticated')
        ->assertSee('Could not load issues');
});
