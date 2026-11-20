<?php

namespace App\Filament\Pages\Settings;

use App\Settings\ReminderSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageReminders extends SettingsPage
{
    protected static string $settings = ReminderSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Due reminders';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'finance']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reminders')->columns(4)->schema([
                Toggle::make('enabled')->label('Send automatic reminders')->columnSpanFull(),
                TextInput::make('days_before')->label('Days before due date')->numeric()->minValue(0)->maxValue(30)->required(),
                TextInput::make('overdue_interval_days')->label('Overdue: repeat every … days')->numeric()->minValue(1)->required(),
                TextInput::make('overdue_max_reminders')->label('Overdue: max reminders')->numeric()->minValue(0)->maxValue(10)->required(),
            ]),
            Section::make('Quiet hours (Addis Ababa time)')->description('No reminders are sent in these windows; they go out at the next allowed run.')->columns(4)->schema([
                TextInput::make('quiet_start')->type('time')->required(),
                TextInput::make('quiet_end')->type('time')->required(),
                Toggle::make('jumuah_pause')->label("Pause during Jumu'ah (Friday)")->inline(false)->columnSpanFull(),
                TextInput::make('jumuah_start')->type('time')->required(),
                TextInput::make('jumuah_end')->type('time')->required(),
            ]),
        ]);
    }
}
