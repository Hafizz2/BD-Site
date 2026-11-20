<?php

namespace App\Filament\Resources\JourneyStepResource\Pages;

use App\Filament\Resources\JourneyStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJourneySteps extends ManageRecords
{
    protected static string $resource = JourneyStepResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
