<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Filament\Resources\DueResource;
use App\Models\Due;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DuesRelationManager extends RelationManager
{
    protected static string $relationship = 'dues';

    protected static ?string $title = 'Dues';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-receipt-percent';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', Due::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return DueResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return DueResource::table($table)->filters([])->headerActions([]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
