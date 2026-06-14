<?php

use App\Filament\Widgets\CostByDepartment;
use App\Filament\Widgets\CostByTask;
use App\Filament\Widgets\CostByUser;
use App\Filament\Widgets\PlatformCostOverview;
use App\Models\Task;
use App\Models\Team;
use App\Models\Usage;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());

    $this->department = Team::factory()->create(['name' => 'Engineering', 'is_personal' => false]);
    $this->member = User::factory()->create(['name' => 'Ada Lovelace']);

    $this->task = Task::factory()->create([
        'team_id' => $this->department->id,
        'user_id' => $this->member->id,
        'name' => 'Ship token dashboard',
    ]);

    Usage::factory()->create([
        'task_id' => $this->task->id,
        'cost_total' => '1.00',
        'tokens_input' => 1000,
        'tokens_output' => 2000,
    ]);

    Usage::factory()->create([
        'task_id' => $this->task->id,
        'cost_total' => '0.50',
        'tokens_input' => 500,
        'tokens_output' => 500,
    ]);
    // Department: $1.50 total cost, 4,000 tokens, 1 task.
});

it('shows platform-wide cost and token totals', function () {
    Livewire::test(PlatformCostOverview::class)
        ->assertSuccessful()
        ->assertSee('$1.50')
        ->assertSee('4K');
});

it('aggregates cost per department', function () {
    Livewire::test(CostByDepartment::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->department])
        ->assertSee('$1.50');
});

it('aggregates cost per user', function () {
    Livewire::test(CostByUser::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->member])
        ->assertSee('Ada Lovelace')
        ->assertSee('$1.50');
});

it('aggregates cost per task', function () {
    Livewire::test(CostByTask::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->task])
        ->assertSee('Ship token dashboard')
        ->assertSee('Engineering')
        ->assertSee('$1.50');
});
