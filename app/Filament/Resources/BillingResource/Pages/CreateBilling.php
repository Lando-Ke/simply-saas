<?php

namespace App\Filament\Resources\BillingResource\Pages;

use App\Filament\Resources\BillingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBilling extends CreateRecord
{
    protected static string $resource = BillingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Create New Subscription';
    }
}
