<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->brandName('Lusso Home Concept')
            ->brandLogo(asset('img/logo-acik.png'))
            ->darkModeBrandLogo(asset('img/logo-acik.png'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('img/favicon.png'))
            // Yalnız açık tema. (Tarayıcıda eskiden kalan "koyu" tercihi de böylece yok sayılır.)
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->font('Lato', provider: \Filament\FontProviders\BunnyFontProvider::class)
            ->colors([
                'primary' => Color::hex('#db8700'),
                'gray' => Color::Stone,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->databaseNotifications(false)
            ->navigationGroups(['Satış', 'Katalog', 'İçerik', 'Ayarlar'])
            ->navigationItems([
                NavigationItem::make('Siteyi görüntüle')
                    ->url(fn () => url('/'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->group('Ayarlar')
                    ->sort(99),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.styles'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
