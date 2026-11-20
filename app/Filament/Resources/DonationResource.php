<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('donor_name')->maxLength(150),
            Toggle::make('is_anonymous')->label('Anonymous')->inline(false),
            TextInput::make('donor_phone')->tel()->maxLength(20)->dehydrateStateUsing(fn ($state) => PhoneNumber::normalize($state) ?? $state),
            TextInput::make('amount')->required()->numeric()->minValue(0.01)->prefix('ETB'),
            Select::make('method')->options(PaymentMethod::class)->required()->native(false),
            TextInput::make('transaction_ref')->maxLength(100),
            TextInput::make('purpose')->maxLength(150),
            Select::make('program_id')->relationship('program', 'name')->searchable()->preload(),
            DatePicker::make('received_at')->required()->default(now())->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('received_at')->date('d M Y')->sortable(),
                TextColumn::make('donor_name')->formatStateUsing(fn ($state, Donation $r) => $r->is_anonymous ? 'Anonymous' : ($state ?: '—'))->searchable(),
                TextColumn::make('amount')->money('ETB')->sortable()->weight('bold')->summarize(Sum::make()->money('ETB')),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('purpose')->limit(40),
                TextColumn::make('program.name')->toggleable(),
            ])
            ->filters([SelectFilter::make('method')->options(PaymentMethod::class), SelectFilter::make('program_id')->relationship('program', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDonations::route('/')];
    }
}
