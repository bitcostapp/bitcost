<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\TaskProvider;
use App\Enums\TaskStatus;
use App\Models\Team;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Task')
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('team.name')
                    ->label('Department')
                    ->badge()
                    ->color('primary')
                    ->placeholder('No department')
                    ->sortable(),

                TextColumn::make('external_provider')
                    ->label('Provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->label())
                    ->color(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->color())
                    ->icon(fn (?string $state): string => TaskProvider::fromExternalProvider($state)->icon())
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TaskStatus $state): string => $state->label())
                    ->color(fn (TaskStatus $state): string => $state === TaskStatus::Completed ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('cost_sum')
                    ->label('Cost')
                    ->money('USD')
                    ->placeholder('$0.00')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('team_id')
                    ->label('Department')
                    ->options(fn (): array => Team::query()
                        ->where('is_personal', false)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options(TaskProvider::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === TaskProvider::Internal->value) {
                            return $query->whereNull('external_provider');
                        }

                        return $query->where('external_provider', $value);
                    }),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(TaskStatus::cases())
                        ->mapWithKeys(fn (TaskStatus $status): array => [$status->value => $status->label()])
                        ->all()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
