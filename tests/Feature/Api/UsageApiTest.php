<?php

use App\Enums\TeamRole;
use App\Models\ModelPricing;
use App\Models\Task;
use App\Models\Team;
use App\Models\Usage;
use App\Models\User;
use Laravel\Passport\Passport;

function usageUser(?Team &$department = null): User
{
    $user = User::factory()->create();
    $department = Team::factory()->create(['is_personal' => false]);
    $department->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($department);

    return $user->refresh();
}

function pricedModel(): ModelPricing
{
    return ModelPricing::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-test',
        'variant' => null,
        'input_price' => 3.0,
        'output_price' => 15.0,
        'cache_read_price' => 0.3,
        'cache_write_price' => 3.75,
        'reasoning_price' => 0.0,
    ]);
}

function usagePayload(array $overrides = []): array
{
    return array_replace([
        'idempotency_key' => 'turn-1',
        'session' => 'ses_abc',
        'provider' => 'anthropic',
        'model' => 'claude-test',
        'tokens' => ['input' => 1000, 'output' => 500, 'reasoning' => 0, 'cache' => ['read' => 0, 'write' => 0]],
    ], $overrides);
}

test('reporting usage requires authentication', function () {
    $task = Task::factory()->create();
    $this->postJson("/api/tasks/{$task->id}/usage", usagePayload())->assertUnauthorized();
});

test('reporting usage stores raw tokens and computes cost', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    pricedModel();
    $task = Task::factory()->for($user)->for($department, 'team')->create();
    $traceId = 'usage:'.$task->id.':turn-1';

    $response = $this
        ->withHeader('X-Bitcost-Trace-ID', $traceId)
        ->postJson("/api/tasks/{$task->id}/usage", usagePayload())
        ->assertCreated();

    $response->assertJsonPath('data.is_priced', true);
    $response->assertJsonPath('data.request_id', $traceId);
    $response->assertHeader('X-Bitcost-Trace-ID', $traceId);
    // 1000*3/1e6 + 500*15/1e6 = 0.0105
    expect((float) $response->json('data.cost_total'))->toBe(0.0105);
    $this->assertDatabaseHas('usages', [
        'task_id' => $task->id,
        'idempotency_key' => 'turn-1',
        'request_id' => $traceId,
        'tokens_input' => 1000,
        'tokens_output' => 500,
        'is_priced' => true,
    ]);
});

test('reporting usage is idempotent on the turn key', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    pricedModel();
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/usage", usagePayload())->assertCreated();
    $this->postJson("/api/tasks/{$task->id}/usage", usagePayload(['tokens' => ['input' => 99]]))->assertOk();

    expect(Usage::where('task_id', $task->id)->count())->toBe(1);
    // The original token counts are preserved (duplicate ignored).
    $this->assertDatabaseHas('usages', ['task_id' => $task->id, 'tokens_input' => 1000]);
});

test('reporting usage to a completed task is rejected', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    pricedModel();
    $task = Task::factory()->completed()->for($user)->for($department, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/usage", usagePayload())->assertStatus(409);
    expect(Usage::where('task_id', $task->id)->count())->toBe(0);
});

test('an unknown model is stored unpriced', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $response = $this->postJson("/api/tasks/{$task->id}/usage", usagePayload(['model' => 'mystery-model']))
        ->assertCreated();

    $response->assertJsonPath('data.is_priced', false);
    $response->assertJsonPath('data.cost_total', null);
    $this->assertDatabaseHas('usages', ['task_id' => $task->id, 'is_priced' => false, 'cost_total' => null]);
});

test('a server-priced turn keeps the server cost and retains the client cost for audit', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    pricedModel();
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $response = $this->postJson("/api/tasks/{$task->id}/usage", usagePayload(['cost' => 99.0]))
        ->assertCreated();

    // Server pricing wins for cost_total; provenance is the model_pricings row.
    expect((float) $response->json('data.cost_total'))->toBe(0.0105);
    $response->assertJsonPath('data.cost_source', 'pricing');
    // The client estimate is still stored alongside it.
    expect((float) $response->json('data.client_cost_total'))->toBe(99.0);
    $this->assertDatabaseHas('usages', [
        'task_id' => $task->id,
        'cost_source' => 'pricing',
        'client_cost_total' => 99.0,
        'is_priced' => true,
    ]);
});

test('an unpriced turn falls back to the client cost', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $response = $this->postJson("/api/tasks/{$task->id}/usage", usagePayload([
        'model' => 'mystery-model',
        'cost' => 0.0042,
    ]))->assertCreated();

    $response->assertJsonPath('data.is_priced', false);
    expect((float) $response->json('data.cost_total'))->toBe(0.0042);
    $response->assertJsonPath('data.cost_source', 'client');
    $this->assertDatabaseHas('usages', [
        'task_id' => $task->id,
        'is_priced' => false,
        'cost_source' => 'client',
        'client_cost_total' => 0.0042,
    ]);
});

test('an unpriced turn with no client cost stays unpriced with no source', function () {
    $user = usageUser($department);
    Passport::actingAs($user);
    $task = Task::factory()->for($user)->for($department, 'team')->create();

    $response = $this->postJson("/api/tasks/{$task->id}/usage", usagePayload(['model' => 'mystery-model']))
        ->assertCreated();

    $response->assertJsonPath('data.cost_total', null);
    $response->assertJsonPath('data.cost_source', null);
    $this->assertDatabaseHas('usages', [
        'task_id' => $task->id,
        'cost_total' => null,
        'cost_source' => null,
        'client_cost_total' => null,
    ]);
});

test('a user cannot report usage to another users task', function () {
    $user = usageUser($department);
    Passport::actingAs($user);

    $other = usageUser($otherDepartment);
    $task = Task::factory()->for($other)->for($otherDepartment, 'team')->create();

    $this->postJson("/api/tasks/{$task->id}/usage", usagePayload())->assertForbidden();
    expect(Usage::where('task_id', $task->id)->count())->toBe(0);
});
