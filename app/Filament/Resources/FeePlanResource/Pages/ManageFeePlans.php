<?php

namespace App\Filament\Resources\FeePlanResource\Pages;

use App\Filament\Resources\FeePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFeePlans extends ManageRecords
{
    protected static string $resource = FeePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
