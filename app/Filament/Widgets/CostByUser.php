<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CostByUser extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Token cost by user';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->withCount('tasks')
                ->withSum('usages as cost_sum', 'cost_total')
                ->withSum('usages as tokens_sum', DB::raw('tokens_input + tokens_output + tokens_reasoning + tokens_cache_read + tokens_cache_write'))
            )
            ->defaultSort('cost_sum', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('currentTeam.name')
                    ->label('Current department')
                    ->placeholder('—'),
                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('tokens_sum')
                    ->label('Tokens')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('cost_sum')
                    ->label('Total cost')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->paginated([10, 25, 50]);
    }
}
