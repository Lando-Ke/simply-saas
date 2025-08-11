<?php

namespace App\Filament\Resources\BrandingResource\Pages;

use App\Filament\Resources\BrandingResource;
use Filament\Resources\Pages\ListRecords;

class ListBranding extends ListRecords
{
    protected static string $resource = BrandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for branding - we edit existing tenants
        ];
    }

    public function getTitle(): string
    {
        return 'Branding Management';
    }
}
