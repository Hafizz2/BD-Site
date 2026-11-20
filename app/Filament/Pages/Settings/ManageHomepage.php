<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Bilingual;
use App\Settings\HomepageSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageHomepage extends SettingsPage
{
    protected static string $settings = HomepageSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Home page';

    protected static ?string $title = 'Home page';

    public const SECTIONS = [
        'about' => ['About (mission / vision / values)', 'heroicon-o-information-circle', 'Cards are edited in the "About cards" tab.'],
        'services' => ['Services', 'heroicon-o-squares-2x2', 'Cards come from Website → Services.'],
        'goals' => ['Goals', 'heroicon-o-flag', 'Items come from Website → Goals.'],
        'journey' => ['Journey steps', 'heroicon-o-map', 'Steps come from Website → Journey steps.'],
        'articles' => ['Latest articles', 'heroicon-o-newspaper', 'Shows the newest published articles.'],
        'events' => ['Upcoming events', 'heroicon-o-calendar-days', 'Shows the next published events.'],
        'gallery' => ['Gallery', 'heroicon-o-photo', 'Shows public albums (featured first).'],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'media']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $sectionTabs = [];
        foreach (self::SECTIONS as $key => [$label, $icon, $help]) {
            $sectionTabs[] = Tab::make($label)->icon($icon)->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Toggle::make("sections.{$key}.enabled")->label('Show this section on the home page'),
                    TextInput::make("sections.{$key}.limit")->label('How many items')->numeric()->minValue(1)->maxValue(24),
                ]),
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make("sections.{$key}.eyebrow")->label('Small label'),
                    TextInput::make("sections.{$key}.eyebrow_am")->label('Small label · አማርኛ'),
                    TextInput::make("sections.{$key}.title")->label('Heading'),
                    TextInput::make("sections.{$key}.title_am")->label('Heading · አማርኛ'),
                    Textarea::make("sections.{$key}.intro")->label('Intro')->rows(2),
                    Textarea::make("sections.{$key}.intro_am")->label('Intro · አማርኛ')->rows(2),
                ]),
            ])->extraAttributes(['title' => $help]);
        }

        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Hero')->icon('heroicon-o-star')->schema([
                    Bilingual::text('hero_eyebrow', 'Small label above the title'),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextInput::make('hero_title')->label('Title')->required(),
                        TextInput::make('hero_title_highlight')->label('Highlighted word (green gradient)'),
                        TextInput::make('hero_title_am')->label('Amharic title line'),
                    ])->columnSpanFull(),
                    Bilingual::textarea('hero_lead', 'Intro paragraph', false, 600, 3),
                    Section::make('Buttons')->columns(3)->schema([
                        TextInput::make('hero_primary_label')->label('Primary button'),
                        TextInput::make('hero_primary_label_am')->label('Primary · አማርኛ'),
                        TextInput::make('hero_primary_url')->label('Primary link')->placeholder('/register'),
                        TextInput::make('hero_secondary_label')->label('Secondary button'),
                        TextInput::make('hero_secondary_label_am')->label('Secondary · አማርኛ'),
                        TextInput::make('hero_secondary_url')->label('Secondary link')->placeholder('/services'),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('show_stats')->label('Show counters (Website → Hero counters)'),
                        Toggle::make('show_verse')->label('Show the Qur\'an verse band (Settings → Site)'),
                    ]),
                ]),
                Tab::make('About cards')->icon('heroicon-o-rectangle-group')->schema([
                    Repeater::make('about_cards')->hiddenLabel()->reorderable()->collapsible()->maxItems(6)
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('title')->required(),
                                TextInput::make('title_am')->label('Title · አማርኛ'),
                                Textarea::make('text')->rows(3),
                                Textarea::make('text_am')->label('Text · አማርኛ')->rows(3),
                            ]),
                        ]),
                    Repeater::make('pillars')->label('Value pills (shown on the last card)')->grid(4)->reorderable()
                        ->schema([TextInput::make('label')->required(), TextInput::make('label_am')->label('· አማርኛ')]),
                ]),
                Tab::make('Sections')->icon('heroicon-o-bars-3-bottom-left')->schema([
                    Tabs::make('sections_tabs')->vertical()->tabs($sectionTabs),
                ]),
                Tab::make('Call to action')->icon('heroicon-o-megaphone')->schema([
                    Toggle::make('show_cta')->label('Show the green call-to-action band (home and inner pages)'),
                    Bilingual::text('cta_title', 'Heading'),
                    Bilingual::textarea('cta_text', 'Text', false, 400, 2),
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        TextInput::make('cta_primary_label')->label('Primary button'),
                        TextInput::make('cta_primary_label_am')->label('Primary · አማርኛ'),
                        TextInput::make('cta_primary_url')->label('Primary link'),
                        TextInput::make('cta_secondary_label')->label('Secondary button'),
                        TextInput::make('cta_secondary_label_am')->label('Secondary · አማርኛ'),
                        TextInput::make('cta_secondary_url')->label('Secondary link'),
                    ])->columnSpanFull(),
                ]),
            ]),
        ]);
    }
}
