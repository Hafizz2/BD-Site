<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Bilingual;
use App\Settings\RegistrationSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageRegistration extends SettingsPage
{
    protected static string $settings = RegistrationSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Registration defaults';

    protected static ?string $title = 'Registration defaults';

    // Reached from Registration forms → "Default messages" (each batch has its own form now).
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'media']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('When there is no open form')
                ->description('Shown if someone opens /register, or a batch link that is closed.')
                ->schema([Bilingual::textarea('closed_message', 'Message', false, 500, 2)]),
            Section::make('Default thank-you page')
                ->description('Used when a batch form has no thank-you message of its own.')
                ->schema([
                    Bilingual::text('thank_you_title', 'Title'),
                    Bilingual::textarea('thank_you_text', 'Text', false, 600, 3),
                ]),
            Section::make('Spam protection (Cloudflare Turnstile)')->collapsed()->schema([
                Toggle::make('turnstile_enabled')->helperText('Requires TURNSTILE_SECRET_KEY in .env. The honeypot and rate limiting are always on.'),
                TextInput::make('turnstile_site_key')->maxLength(100),
            ]),
        ]);
    }
}
