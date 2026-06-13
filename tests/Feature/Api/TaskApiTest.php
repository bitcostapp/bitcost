<?php

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Laravel\Passport\Passport;

/**
 * Create a user whose current team is a non-personal Department.
 */
function userInDepartment(?Team &$department = null): User
{
    $user = User::factory()->create();
    $department = Team::factory()->create(['is_personal' => false]);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    return $user->refresh();
}

test('listing tasks requires authentication', function () {
    $this->getJson('/api/tasks')->assertUnauthorized();
});

test('creating a task requires authentication', function () {
    $this->postJson('/api/tasks', ['name' => 'Nope'])->assertUnauthorized();
});

test('index returns only the open tasks owned by the user in their department', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $open = Task::factory()->for($user)->for($department, 'team')->create(['name' => 'Open one']);
    Task::factory()->completed()->for($user)->for($department, 'team')->create(['name' => 'Done']);

    $response = $this->getJson('/api/tasks')->assertOk();

    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $open->id);
    $response->assertJsonPath('data.0.status', TaskStatus::Open->value);
});

test('index does not leak tasks from other users or other departments', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $other = userInDepartment($otherDepartment);
    Task::factory()->for($other)->for($otherDepartment, 'team')->create();
    Task::factory()->for($other)->for($department, 'team')->create(); // same dept, different user

    $this->getJson('/api/tasks')->assertOk()->assertJsonCount(0, 'data');
});

test('a user with only a personal team sees their department-less tasks', function () {
    $user = User::factory()->create(); // factory gives a personal current team
    expect($user->currentTeam->is_personal)->toBeTrue();
    Passport::actingAs($user);

    $task = Task::factory()->for($user)->create(['team_id' => null]);

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $task->id);
});

test('store creates an open task scoped to the current department', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $response = $this->postJson('/api/tasks', ['name' => 'Build auth'])->assertCreated();

    $response->assertJsonPath('data.name', 'Build auth');
    $response->assertJsonPath('data.status', TaskStatus::Open->value);
    $this->assertDatabaseHas('tasks', [
        'name' => 'Build auth',
        'user_id' => $user->id,
        'team_id' => $department->id,
        'status' => TaskStatus::Open->value,
    ]);
});

test('store for a personal-team user creates a department-less task', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->postJson('/api/tasks', ['name' => 'Solo work'])->assertCreated();

    $this->assertDatabaseHas('tasks', [
        'name' => 'Solo work',
        'user_id' => $user->id,
        'team_id' => null,
    ]);
});

test('store rejects a missing name', function () {
    $user = userInDepartment();
    Passport::actingAs($user);

    $this->postJson('/api/tasks', [])->assertUnprocessable()->assertJsonValidationErrorFor('name');
});

test('completing a task locks it and removes it from the list', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $this->patchJson("/api/tasks/{$task->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', TaskStatus::Completed->value);

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => TaskStatus::Completed->value]);
    expect($task->fresh()->completed_at)->not->toBeNull();

    $this->getJson('/api/tasks')->assertOk()->assertJsonCount(0, 'data');
});

test('completing a task is idempotent', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $task = Task::factory()->completed()->for($user)->for($department, 'team')->create();
    $completedAt = $task->completed_at;

    $this->patchJson("/api/tasks/{$task->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', TaskStatus::Completed->value);

    expect($task->fresh()->completed_at->equalTo($completedAt))->toBeTrue();
});

test('a user cannot complete another users task', function () {
    $user = userInDepartment($department);
    Passport::actingAs($user);

    $other = userInDepartment($otherDepartment);
    $task = Task::factory()->for($other)->for($otherDepartment, 'team')->create();

    $this->patchJson("/api/tasks/{$task->id}/complete")->assertForbidden();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => TaskStatus::Open->value]);
});
