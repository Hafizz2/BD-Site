<?php

namespace App\Filament\Resources\GalleryAlbumResource\Pages;

use App\Filament\Resources\GalleryAlbumResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryAlbum extends EditRecord
{
    protected static string $resource = GalleryAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
