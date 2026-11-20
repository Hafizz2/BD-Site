<?php

namespace App\Filament\Resources\DueResource\Pages;

use App\Filament\Resources\DueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDues extends ManageRecords
{
    protected static string $resource = DueResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
