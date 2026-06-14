<?php

use App\Enums\TaskProvider;
use App\Filament\Pages\WorkProviders;
use App\Models\User;
use App\Models\WorkProviderConnection;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());
});

it('renders the work providers page with github and jira', function () {
    Livewire::test(WorkProviders::class)
        ->assertSuccessful()
        ->assertSee('GitHub')
        ->assertSee('Jira')
        ->assertSee('Not connected');
});

it('connects a work provider', function () {
    Livewire::test(WorkProviders::class)
        ->call('connect', TaskProvider::Github->value)
        ->assertSee('Connected');

    $connection = WorkProviderConnection::query()->where('provider', 'github')->sole();

    expect($connection->isConnected())->toBeTrue();
});

it('disconnects a work provider', function () {
    WorkProviderConnection::query()->create([
        'provider' => TaskProvider::Jira->value,
        'connected_at' => now(),
    ]);

    Livewire::test(WorkProviders::class)
        ->call('disconnect', TaskProvider::Jira->value);

    expect(WorkProviderConnection::query()->where('provider', 'jira')->sole()->isConnected())
        ->toBeFalse();
});

it('rejects toggling a non-work-provider', function () {
    Livewire::test(WorkProviders::class)
        ->call('connect', TaskProvider::Internal->value)
        ->assertStatus(404);

    expect(WorkProviderConnection::query()->count())->toBe(0);
});
