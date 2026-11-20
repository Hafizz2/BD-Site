<?php

namespace App\Filament\Pages\Settings;

use App\Settings\NavigationSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageNavigation extends SettingsPage
{
    protected static string $settings = NavigationSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $title = 'Website menus';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'media']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $link = fn () => [
            TextInput::make('label')->required()->maxLength(40),
            TextInput::make('label_am')->label('Label · አማርኛ')->maxLength(40),
            TextInput::make('url')->required()->maxLength(255)->placeholder('/about or https://…')
                ->helperText('Pages: / · /about · /goals · /services · /programs · /articles · /events · /gallery · /team · /announcements · /faq · /contact · /donate · /register · /p/{custom-page}'),
        ];

        return $schema->components([
            Section::make('Header menu')->description('Items with sub-links show a dropdown on desktop.')->schema([
                Repeater::make('header_links')->hiddenLabel()->reorderable()->collapsible()->cloneable()
                    ->itemLabel(fn (array $state) => $state['label'] ?? null)
                    ->schema([
                        Grid::make(3)->schema($link()),
                        Repeater::make('children')->label('Dropdown links')->reorderable()->collapsed()->defaultItems(0)
                            ->itemLabel(fn (array $state) => $state['label'] ?? null)
                            ->schema([Grid::make(3)->schema($link())]),
                    ]),
                Grid::make(3)->schema([
                    TextInput::make('header_cta_label')->label('Button label'),
                    TextInput::make('header_cta_label_am')->label('Button · አማርኛ'),
                    TextInput::make('header_cta_url')->label('Button link'),
                ]),
            ]),
            Section::make('Footer columns')->description('The first two columns are shown as link lists; links in further columns appear under "Contact".')->schema([
                Repeater::make('footer_columns')->hiddenLabel()->reorderable()->collapsible()->maxItems(4)
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')->required(),
                            TextInput::make('title_am')->label('Title · አማርኛ'),
                        ]),
                        Repeater::make('links')->reorderable()->collapsed()->itemLabel(fn (array $state) => $state['label'] ?? null)
                            ->schema([Grid::make(3)->schema($link())]),
                    ]),
            ]),
        ]);
    }
}
