<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\Team;
use App\Models\Usage;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class PlatformCostOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    /**
     * SQL expression that totals every token bucket on a usage row.
     */
    private const TOKEN_SUM = 'tokens_input + tokens_output + tokens_reasoning + tokens_cache_read + tokens_cache_write';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $totalCost = (float) Usage::query()->sum('cost_total');
        $totalTokens = (int) Usage::query()->sum(DB::raw(self::TOKEN_SUM));

        return [
            Stat::make('Total token cost', '$'.number_format($totalCost, 2))
                ->description('Across all departments & tasks')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total tokens', Number::abbreviate($totalTokens))
                ->description('Input, output, reasoning & cache')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info'),
            Stat::make('Tasks tracked', Number::format(Task::query()->count()))
                ->description('Across '.Team::query()->where('is_personal', false)->count().' departments')
                ->descriptionIcon('heroicon-m-clipboard-document-list'),
            Stat::make('Users', Number::format(User::query()->count()))
                ->description('Reporting usage')
                ->descriptionIcon('heroicon-m-users'),
        ];
    }
}
