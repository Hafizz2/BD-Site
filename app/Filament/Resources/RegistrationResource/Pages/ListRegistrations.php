<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Enums\RegistrationStatus;
use App\Filament\Resources\RegistrationResource;
use App\Models\Registration;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected ?string $subheading = 'Everyone who registered through a batch link. Tap a phone number to call, then use "Update status".';

    /** All registrations, with one tab per step. */
    public function getTabs(): array
    {
        $counts = Registration::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $tabs = ['all' => Tab::make('All')->badge($counts->sum() ?: null)];
        foreach (RegistrationStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge($counts[$status->value] ?? null)
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
