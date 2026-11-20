<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Batch;
use App\Support\RegistrationForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistration extends EditRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Stored answers are [key => [q, a]]; the form edits plain [key => value]. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['answers'] = RegistrationForm::unpack($data['answers'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $batch = Batch::find($data['batch_id'] ?? $this->getRecord()->batch_id);
        $packed = RegistrationForm::pack($batch, $data['answers'] ?? []);

        // Keep answers to questions that were later removed from the batch's form.
        $old = collect($this->getRecord()->answers ?? [])->except(array_column(RegistrationForm::custom($batch, false), 'key'));
        $data['answers'] = $old->merge($packed)->all() ?: null;

        return $data;
    }
}
