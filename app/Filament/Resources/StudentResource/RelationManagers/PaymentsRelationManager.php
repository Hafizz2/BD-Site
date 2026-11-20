<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-banknotes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return PaymentResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return PaymentResource::table($table)->filters([])->headerActions([]);
    }
}
