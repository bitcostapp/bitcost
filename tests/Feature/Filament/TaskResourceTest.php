<?php

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());

    $this->department = Team::factory()->create(['name' => 'Engineering', 'is_personal' => false]);
    $this->member = User::factory()->create(['name' => 'Ada Lovelace']);
    $this->department->members()->attach($this->member, ['role' => 'member']);
});

it('lists tasks across departments', function () {
    $task = Task::factory()->create([
        'team_id' => $this->department->id,
        'user_id' => $this->member->id,
        'name' => 'Ship token dashboard',
    ]);

    Livewire::test(ListTasks::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$task])
        ->assertSee('Ship token dashboard')
        ->assertSee('Engineering');
});

it('admin can add an internal task unassigned to a department', function () {
    Livewire::test(CreateTask::class)
        ->fillForm([
            'name' => 'Refactor auth flow',
            'team_id' => $this->department->id,
            'user_id' => null,
            'status' => TaskStatus::Open->value,
            'external_provider' => TaskProvider::Internal->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::query()->where('name', 'Refactor auth flow')->sole();

    expect($task->team_id)->toBe($this->department->id)
        ->and($task->user_id)->toBeNull()
        ->and($task->external_provider)->toBeNull()
        ->and($task->provider)->toBe(TaskProvider::Internal);
});

it('admin can add a github task with an external url and owner', function () {
    Livewire::test(CreateTask::class)
        ->fillForm([
            'name' => 'Fix webhook retries',
            'team_id' => $this->department->id,
            'user_id' => $this->member->id,
            'status' => TaskStatus::Open->value,
            'external_provider' => TaskProvider::Github->value,
            'external_url' => 'https://github.com/acme/repo/issues/42',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::query()->where('name', 'Fix webhook retries')->sole();

    expect($task->external_provider)->toBe('github')
        ->and($task->external_url)->toBe('https://github.com/acme/repo/issues/42')
        ->and($task->user_id)->toBe($this->member->id)
        ->and($task->provider)->toBe(TaskProvider::Github);
});

it('requires an external url for a work-provider task', function () {
    Livewire::test(CreateTask::class)
        ->fillForm([
            'name' => 'Broken jira task',
            'team_id' => $this->department->id,
            'status' => TaskStatus::Open->value,
            'external_provider' => TaskProvider::Jira->value,
            'external_url' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['external_url']);
});

it('admin can edit a task', function () {
    $task = Task::factory()->create([
        'team_id' => $this->department->id,
        'user_id' => $this->member->id,
        'name' => 'Old name',
        'status' => TaskStatus::Open,
    ]);

    Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->fillForm([
            'name' => 'New name',
            'status' => TaskStatus::Completed->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($task->refresh()->name)->toBe('New name')
        ->and($task->status)->toBe(TaskStatus::Completed);
});

it('filters tasks by provider', function () {
    $internal = Task::factory()->create([
        'team_id' => $this->department->id,
        'user_id' => $this->member->id,
        'external_provider' => null,
    ]);

    $github = Task::factory()->create([
        'team_id' => $this->department->id,
        'user_id' => $this->member->id,
        'external_provider' => 'github',
    ]);

    Livewire::test(ListTasks::class)
        ->filterTable('provider', TaskProvider::Internal->value)
        ->assertCanSeeTableRecords([$internal])
        ->assertCanNotSeeTableRecords([$github]);
});
