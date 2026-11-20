<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('Ye Birhan Dana')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/fav64.png'))
            ->colors([
                'primary' => Color::hex('#0b3d2a'),
                'gold' => Color::hex('#c9a45c'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Sky,
                'gray' => Color::Stone,
            ])
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->navigationGroups([
                NavigationGroup::make('People')->icon('heroicon-o-users'),
                NavigationGroup::make('Programs')->icon('heroicon-o-academic-cap'),
                NavigationGroup::make('Finance')->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Communication')->icon('heroicon-o-megaphone'),
                NavigationGroup::make('Website')->icon('heroicon-o-globe-alt')->collapsed(),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth')->collapsed(),
                NavigationGroup::make('System')->icon('heroicon-o-shield-check')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->databaseNotifications()
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => Blade::render('@include("filament.theme")'))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): string => Blade::render('<a href="{{ url(\'/\') }}" target="_blank" class="ybd-visit">View site ↗</a>'))
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('System'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
