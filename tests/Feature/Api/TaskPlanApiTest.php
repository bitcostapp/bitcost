<?php

use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Laravel\Passport\Passport;

function planUser(?Team &$department = null): User
{
    $user = User::factory()->create();
    $department = Team::factory()->create(['is_personal' => false]);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    return $user->refresh();
}

test('attaching a plan requires authentication', function () {
    $task = Task::factory()->create();
    $this->postJson("/api/tasks/{$task->id}/plans", ['body' => 'x'])->assertUnauthorized();
});

test('a plan can be attached and listed', function () {
    $user = planUser($department);
    Passport::actingAs($user);
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/plans", ['title' => 'Design', 'body' => '# Plan'])
        ->assertCreated()
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.body', '# Plan');

    $this->getJson("/api/tasks/{$task->id}/plans")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Design');
});

test('attaching plans increments the version', function () {
    $user = planUser($department);
    Passport::actingAs($user);
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/plans", ['body' => 'v1'])->assertCreated()->assertJsonPath('data.version', 1);
    $this->postJson("/api/tasks/{$task->id}/plans", ['body' => 'v2'])->assertCreated()->assertJsonPath('data.version', 2);

    expect($task->plans()->count())->toBe(2);
});

test('a user cannot attach a plan to another users task', function () {
    $user = planUser($department);
    Passport::actingAs($user);

    $other = planUser($otherDepartment);
    $task = Task::factory()->for($other)->for($otherDepartment, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/plans", ['body' => 'x'])->assertForbidden();
    expect($task->plans()->count())->toBe(0);
});
