<?php

namespace Embiken\BikeRentals\Filament\Resources\Customers\Pages;

use Embiken\BikeRentals\Actions\RevokeCustomerMyRentalAccessAction;
use Embiken\BikeRentals\Filament\Resources\Customers\CustomerResource;
use Embiken\BikeRentals\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revokeMyRentalAccess')
                ->label('Revoke MyRental access')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof Customer
                    && $this->record->reservations()->whereNotNull('myrental_token')->exists())
                ->action(function (): void {
                    if (! $this->record instanceof Customer) {
                        return;
                    }

                    $count = app(RevokeCustomerMyRentalAccessAction::class)($this->record);

                    Notification::make()
                        ->title("Revoked access for {$count} reservation(s)")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
