<?php

use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\TaskPlan;
use App\Models\Team;
use App\Models\Usage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create a user who is a member of a fresh Department and acting on it.
 *
 * @return array{0: User, 1: Team}
 */
function departmentMember(): array
{
    $user = User::factory()->create();
    $department = Team::factory()->create(['name' => 'Engineering']);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    return [$user, $department];
}

function showTaskUrl(Team $department, Task $task): string
{
    return route('tasks.show', ['current_team' => $department->slug, 'task' => $task->id]);
}

function updateTaskUrl(Team $department, Task $task): string
{
    return route('tasks.update', ['current_team' => $department->slug, 'task' => $task->id]);
}

test('a department member can view a task in their department', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'name' => 'Fix login',
        'content' => '# Plan the fix',
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $task));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->where('task.id', $task->id)
        ->where('task.name', 'Fix login')
        ->where('task.content', '# Plan the fix')
        ->where('task.status', 'open')
        ->where('task.owner.name', $user->name)
        ->where('task.departmentName', 'Engineering'),
    );
});

test('a department member can update a task content', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'content' => null,
    ]);

    $this->actingAs($user)
        ->patch(updateTaskUrl($department, $task), ['content' => '## New description'])
        ->assertRedirect();

    expect($task->fresh()->content)->toBe('## New description');
});

test('a completed task content stays editable', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->completed()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->patch(updateTaskUrl($department, $task), ['content' => 'edited after completion'])
        ->assertRedirect();

    expect($task->fresh()->content)->toBe('edited after completion');
});

test('a task in another department cannot be updated and returns 404', function () {
    [$user, $department] = departmentMember();

    $otherDepartment = Team::factory()->create();
    $foreignTask = Task::factory()->create([
        'team_id' => $otherDepartment->id,
        'user_id' => $user->id,
        'content' => 'original',
    ]);

    $this->actingAs($user)
        ->patch(updateTaskUrl($department, $foreignTask), ['content' => 'hacked'])
        ->assertNotFound();

    expect($foreignTask->fresh()->content)->toBe('original');
});

test('task usage is grouped into sessions ordered chronologically', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    // Session A: two turns, earliest.
    Usage::factory()->for($task)->create([
        'session' => 'ses_a', 'tokens_input' => 1000, 'tokens_output' => 500,
        'cost_total' => '4.00', 'currency' => 'USD', 'created_at' => now()->subHours(2),
    ]);
    Usage::factory()->for($task)->create([
        'session' => 'ses_a', 'tokens_input' => 2000, 'tokens_output' => 1000,
        'cost_total' => '8.00', 'currency' => 'USD', 'created_at' => now()->subHours(2)->addMinute(),
    ]);
    // Session B: one turn, middle.
    Usage::factory()->for($task)->create([
        'session' => 'ses_b', 'tokens_input' => 300, 'tokens_output' => 200,
        'cost_total' => '1.50', 'currency' => 'USD', 'created_at' => now()->subHour(),
    ]);
    // Unattributed (null session): one turn, latest.
    Usage::factory()->for($task)->create([
        'session' => null, 'tokens_input' => 100, 'tokens_output' => 100,
        'cost_total' => '0.50', 'currency' => 'USD', 'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $task));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->has('sessions', 3)
        ->where('sessions.0.session', 'ses_a')
        ->where('sessions.0.turns', 2)
        ->where('sessions.0.tokensInput', 3000)
        ->where('sessions.0.tokensOutput', 1500)
        ->where('sessions.0.tokensTotal', 4500)
        ->where('sessions.0.costTotal', 12)
        ->where('sessions.1.session', 'ses_b')
        ->where('sessions.1.turns', 1)
        ->where('sessions.2.session', '—')
        ->where('sessions.2.turns', 1)
        ->where('totals.sessionCount', 3)
        ->where('totals.turns', 4)
        ->where('totals.tokensInput', 3400)
        ->where('totals.tokensOutput', 1800)
        ->where('totals.costTotal', 14)
        ->where('currency', 'USD'),
    );
});

test('a task from another department cannot be viewed and returns 404', function () {
    [$user, $department] = departmentMember();

    $otherDepartment = Team::factory()->create();
    $foreignTask = Task::factory()->create([
        'team_id' => $otherDepartment->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $foreignTask));

    $response->assertNotFound();
});

test('a task with no usage renders empty sessions and zero totals', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $task));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->has('sessions', 0)
        ->where('totals.turns', 0)
        ->where('totals.costTotal', 0)
        ->where('currency', null),
    );
});

test('a completed task still renders for an authorized member', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->completed()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $task));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->where('task.status', 'completed'),
    );
});

test('the latest plan is passed through to the page', function () {
    [$user, $department] = departmentMember();

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    TaskPlan::factory()->for($task)->create(['title' => 'v1', 'body' => 'old', 'version' => 1]);
    TaskPlan::factory()->for($task)->create([
        'title' => 'Auth redesign',
        'body' => '# Heading',
        'version' => 2,
    ]);

    $response = $this->actingAs($user)->get(showTaskUrl($department, $task));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tasks/show')
        ->where('latestPlan.title', 'Auth redesign')
        ->where('latestPlan.body', '# Heading'),
    );
});
