<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Member;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** Every member is listed; only those who agreed to give money are "contributors" and get monthly dues. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All members'),
            'contributors' => Tab::make('Contributors')
                ->icon('heroicon-o-banknotes')
                ->badge(fn () => Member::whereNotNull('monthly_contribution_plan_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('monthly_contribution_plan_id')),
            'non_contributors' => Tab::make('Non-contributors')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('monthly_contribution_plan_id')),
        ];
    }
}
