<?php

namespace App\Filament\Resources\BrandingResource\Pages;

use App\Filament\Resources\BrandingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBranding extends EditRecord
{
    protected static string $resource = BrandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Changes')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    Notification::make()
                        ->title('Preview')
                        ->body('Branding preview functionality will be implemented soon.')
                        ->info()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Branding updated successfully!';
    }

    public function getTitle(): string
    {
        return 'Edit Branding - ' . ($this->record->data['name'] ?? 'Unknown Tenant');
    }
}
