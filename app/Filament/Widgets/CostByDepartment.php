<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CostByDepartment extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Token cost by department';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Team::query()
                ->where('is_personal', false)
                ->withCount('tasks')
                ->withSum('usages as cost_sum', 'cost_total')
                ->withSum('usages as tokens_sum', DB::raw('tokens_input + tokens_output + tokens_reasoning + tokens_cache_read + tokens_cache_write'))
            )
            ->defaultSort('cost_sum', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Department')
                    ->searchable()
                    ->weight('bold'),
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
