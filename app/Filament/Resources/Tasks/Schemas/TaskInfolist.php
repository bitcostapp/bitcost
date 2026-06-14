<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Task')
                    ->columns(2)
                    ->components([
                        TextEntry::make('name')
                            ->label('Task name')
                            ->weight('bold')
                            ->columnSpanFull(),

                        TextEntry::make('team.name')
                            ->label('Department')
                            ->badge()
                            ->color('primary')
                            ->placeholder('No department'),

                        TextEntry::make('user.name')
                            ->label('Owner')
                            ->placeholder('Unassigned'),

                        TextEntry::make('external_provider')
                            ->label('Provider')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->label())
                            ->color(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->color())
                            ->icon(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->icon()),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (TaskStatus $state): string => $state->label())
                            ->color(fn (TaskStatus $state): string => $state === TaskStatus::Completed ? 'success' : 'warning'),

                        TextEntry::make('external_url')
                            ->label('External link')
                            ->url(fn (Task $record): ?string => $record->external_url, shouldOpenInNewTab: true)
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('content')
                            ->label('Description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Usage & cost')
                    ->columns(3)
                    ->components([
                        TextEntry::make('cost_sum')
                            ->label('Total cost')
                            ->money('USD')
                            ->placeholder('$0.00'),

                        TextEntry::make('tokens')
                            ->label('Total tokens')
                            ->state(fn (Task $record): int => (int) $record->usages()
                                ->sum(DB::raw('tokens_input + tokens_output + tokens_reasoning + tokens_cache_read + tokens_cache_write')))
                            ->numeric(),

                        TextEntry::make('turns')
                            ->label('Usage turns')
                            ->state(fn (Task $record): int => $record->usages()->count())
                            ->numeric(),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A'),

                        TextEntry::make('completed_at')
                            ->label('Completed')
                            ->dateTime('M j, Y g:i A')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
