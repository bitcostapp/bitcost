<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Task name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('team_id')
                            ->label('Department')
                            ->options(fn (): array => Team::query()
                                ->where('is_personal', false)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, callable $set) => $set('user_id', null)),

                        Select::make('user_id')
                            ->label('Owner')
                            ->placeholder('Unassigned')
                            ->helperText('Leave empty to add the task to the department backlog.')
                            ->options(fn (Get $get): array => $get('team_id')
                                ? User::query()
                                    ->whereRelation('teams', 'teams.id', $get('team_id'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->nullable(),

                        Select::make('status')
                            ->label('Status')
                            ->options(collect(TaskStatus::cases())
                                ->mapWithKeys(fn (TaskStatus $status): array => [$status->value => $status->label()])
                                ->all())
                            ->default(TaskStatus::Open->value)
                            ->selectablePlaceholder(false)
                            ->required(),

                        Select::make('external_provider')
                            ->label('Provider')
                            ->options(TaskProvider::options())
                            ->default(TaskProvider::Internal->value)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live()
                            ->afterStateHydrated(fn ($state, callable $set) => $set(
                                'external_provider',
                                $state ?? TaskProvider::Internal->value,
                            ))
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state === TaskProvider::Internal->value ? null : $state),

                        TextInput::make('external_url')
                            ->label('External URL')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get): bool => $get('external_provider') !== TaskProvider::Internal->value)
                            ->required(fn (Get $get): bool => $get('external_provider') !== TaskProvider::Internal->value),

                        Textarea::make('content')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
