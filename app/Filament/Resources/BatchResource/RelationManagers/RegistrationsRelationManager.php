<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use App\Filament\Resources\RegistrationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/** The batch's registrations, with the same Call / Update status buttons as the Registrations page. */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Registrations';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return RegistrationResource::table($table)
            ->filters([]); // already this batch
    }
}
