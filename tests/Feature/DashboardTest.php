<?php

use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\TaskPlan;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\Usage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard includes open tasks for the current department', function () {
    $user = User::factory()->create();
    $department = Team::factory()->create(['name' => 'Engineering']); // is_personal = false
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    $openTask = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'name' => 'Fix login',
    ]);

    // Excluded: completed task in the same department.
    Task::factory()->completed()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
    ]);

    // Excluded: open task in a different department.
    $otherDepartment = Team::factory()->create();
    Task::factory()->create(['team_id' => $otherDepartment->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('departmentName', 'Engineering')
        ->has('departmentTasks', 1)
        ->where('departmentTasks.0.id', $openTask->id)
        ->where('departmentTasks.0.name', 'Fix login')
        ->where('departmentTasks.0.status', 'open')
        ->where('departmentTasks.0.owner.name', $user->name)
        ->where('departmentTasks.0.usageCount', 0)
        ->where('departmentTasks.0.tokensInput', 0)
        ->where('departmentTasks.0.tokensOutput', 0)
        ->where('departmentTasks.0.costTotal', 0)
        ->where('departmentTasks.0.currency', null)
        ->where('departmentTasks.0.planTitle', null),
    );
});

test('dashboard shows no department tasks for a user without a department', function () {
    $user = User::factory()->create(); // currentTeam is a personal team

    // An unrelated open task in some other department must not leak in.
    Task::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('departmentName', null)
        ->has('departmentTasks', 0),
    );
});

test('dashboard department tasks include usage and plan aggregates', function () {
    $user = User::factory()->create();
    $department = Team::factory()->create(['name' => 'Engineering']);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    $task = Task::factory()->create([
        'team_id' => $department->id,
        'user_id' => $user->id,
        'name' => 'Fix login',
    ]);

    Usage::factory()->for($task)->create([
        'tokens_input' => 1000,
        'tokens_output' => 500,
        'cost_total' => '4.00',
        'currency' => 'USD',
    ]);
    Usage::factory()->for($task)->create([
        'tokens_input' => 2000,
        'tokens_output' => 1000,
        'cost_total' => '8.40',
        'currency' => 'USD',
    ]);
    TaskPlan::factory()->for($task)->create(['title' => 'Auth redesign', 'version' => 1]);
    TaskPlan::factory()->for($task)->create(['title' => 'Auth redesign v2', 'version' => 2]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('departmentTasks', 1)
        ->where('departmentTasks.0.usageCount', 2)
        ->where('departmentTasks.0.tokensInput', 3000)
        ->where('departmentTasks.0.tokensOutput', 1500)
        ->where('departmentTasks.0.costTotal', 12.4)
        ->where('departmentTasks.0.currency', 'USD')
        ->where('departmentTasks.0.planTitle', 'Auth redesign v2'),
    );
});