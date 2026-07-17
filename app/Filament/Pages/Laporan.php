<?php

namespace App\Filament\Pages;

use App\Http\Controllers\AdminReportController;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Laporan extends Page
{
    protected static ?string $slug = 'laporan';

    protected static ?string $title = 'Laporan';

    protected static string|UnitEnum|null $navigationGroup = 'Website Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.laporan';

    protected function getViewData(): array
    {
        return app(AdminReportController::class)->reportData(request());
    }
}
