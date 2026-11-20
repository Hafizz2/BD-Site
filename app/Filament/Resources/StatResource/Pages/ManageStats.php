<?php

namespace App\Filament\Resources\StatResource\Pages;

use App\Filament\Resources\StatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStats extends ManageRecords
{
    protected static string $resource = StatResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
