<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\EventStatsOverview;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\PreventAdminPageCaching;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class FilamentAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(config('branding.admin_title'))
            ->brandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset(config('branding.favicon')))
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn () => view('filament.components.collapsed-brand'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<script>document.title = '.json_encode(config('branding.admin_browser_title')).'; document.addEventListener("livewire:navigated", () => document.title = '.json_encode(config('branding.admin_browser_title')).');</script>',
            )
            ->globalSearchDebounce('300ms')
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                'Data Management',
                'Website Management',
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                EventStatsOverview::class,
            ])
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
                PreventAdminPageCaching::class,
            ])
            ->authMiddleware([
                EnsureUserIsAdmin::class,
            ]);
    }
}
