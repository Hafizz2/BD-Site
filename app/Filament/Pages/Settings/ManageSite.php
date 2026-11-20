<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Bilingual;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageSite extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Site & contact';

    protected static ?string $title = 'Site & contact settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'media']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Identity')->icon('heroicon-o-sparkles')->schema([
                    Bilingual::text('site_name', 'Site name', true),
                    Bilingual::text('tagline', 'Tagline'),
                    Bilingual::textarea('meta_description', 'Search engine description (SEO)', false, 300, 2),
                    Bilingual::textarea('footer_about', 'Footer "about" text', false, 400, 2),
                    Bilingual::text('copyright', 'Copyright line (year is added automatically)'),
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        FileUpload::make('logo_path')->label('Logo (PNG, transparent)')->disk('public')->directory('branding')->image()->maxSize(2048)
                            ->helperText('Leave empty to use the default logo.'),
                        FileUpload::make('og_image_path')->label('Social sharing image (1200×630)')->disk('public')->directory('branding')->image()->maxSize(4096),
                    ])->columnSpanFull(),
                ]),
                Tab::make('Contact')->icon('heroicon-o-phone')->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextInput::make('phone')->tel()->maxLength(30),
                        TextInput::make('phone_secondary')->tel()->maxLength(30),
                        TextInput::make('email')->email()->maxLength(150),
                    ])->columnSpanFull(),
                    Bilingual::text('address', 'Address'),
                    Bilingual::text('office_hours', 'Office hours'),
                    TextInput::make('map_embed_url')->label('Google Maps embed URL')->url()->maxLength(1000)
                        ->helperText('Google Maps → Share → Embed a map → copy the src="…" link.'),
                ]),
                Tab::make('Social')->icon('heroicon-o-share')->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('telegram_url')->url()->prefixIcon('heroicon-o-paper-airplane'),
                        TextInput::make('facebook_url')->url(),
                        TextInput::make('youtube_url')->url(),
                        TextInput::make('tiktok_url')->url(),
                        TextInput::make('instagram_url')->url(),
                    ]),
                ]),
                Tab::make('Qur\'an verse')->icon('heroicon-o-book-open')->schema([
                    TextInput::make('verse_arabic')->label('Arabic text')->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar', 'style' => 'font-size:1.3rem']),
                    Bilingual::text('verse_translation', 'Translation', false, 500),
                    Bilingual::text('verse_reference', 'Reference (e.g. "Surah An-Nahl · 16:125")'),
                ]),
            ]),
        ]);
    }
}
