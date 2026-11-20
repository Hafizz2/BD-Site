<?php

namespace App\Filament\Pages\Settings;

use App\Enums\PaymentMethod;
use App\Filament\Support\Bilingual;
use App\Settings\PaymentSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManagePayments extends SettingsPage
{
    protected static string $settings = PaymentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Payment accounts';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'finance']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bank & mobile money accounts')
                ->description('Shown on the Donate page.')
                ->schema([
                    Repeater::make('accounts')->hiddenLabel()->reorderable()->collapsible()
                        ->itemLabel(fn (array $state) => trim(($state['bank_name'] ?? '').' · '.($state['account_number'] ?? ''), ' ·'))
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                Select::make('method')->options(PaymentMethod::class)->required()->native(false),
                                TextInput::make('bank_name')->required(),
                                TextInput::make('account_name')->required(),
                                TextInput::make('account_number')->required(),
                                TextInput::make('note')->columnSpan(2),
                                TextInput::make('note_am')->label('Note · አማርኛ')->columnSpan(2),
                                Toggle::make('show_on_donate')->label('Show on Donate page')->default(true),
                            ]),
                        ]),
                ]),
            Section::make('Texts')->schema([
                Bilingual::textarea('payment_instructions', 'Payment instructions (bot)', false, 600, 2),
                Bilingual::textarea('donate_intro', 'Donate page intro', false, 600, 2),
            ]),
        ]);
    }
}
