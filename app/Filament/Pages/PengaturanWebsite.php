<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PengaturanWebsite extends Page
{
    protected static ?string $slug = 'pengaturan-website';

    protected static ?string $title = 'Pengaturan Website';

    protected static string|UnitEnum|null $navigationGroup = 'Website Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.pengaturan-website';

    protected function getViewData(): array
    {
        return [
            'setting' => SiteSetting::current(),
            'events' => Event::orderBy('title')->get(),
        ];
    }
}
