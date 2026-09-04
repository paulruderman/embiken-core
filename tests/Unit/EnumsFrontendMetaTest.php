<?php

use App\Enums\BikeAssignmentPolicy;
use App\Enums\ReturnSituation;
use App\Enums\StaffRole;
use App\Enums\Weekday;
use Embiken\BikeFleet\Enums\BikeSituation;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeRentals\Enums\BikeReservationStatus;
use Embiken\BikeRentals\Enums\ConfirmThreshold;
use Embiken\BikeRentals\Enums\PackageMeter;
use Embiken\BikeRentals\Enums\ReservationChannel;
use Embiken\BikeRentals\Enums\ReservationStage;
use Embiken\BikeRentals\Enums\TransactionKind;
use Embiken\BikeRentals\Enums\TransactionStatus;

/**
 * @return list<class-string>
 */
function domainEnumClasses(): array
{
    return [
        ReservationStage::class,
        BikeSituation::class,
        BikeReservationStatus::class,
        PackageMeter::class,
        ConfirmThreshold::class,
        BikeAssignmentPolicy::class,
        ReturnSituation::class,
        ReservationChannel::class,
        StaffRole::class,
        TransactionKind::class,
        TransactionStatus::class,
        ServiceStage::class,
        Weekday::class,
    ];
}

test('every domain enum frontend lookup table covers all cases with label color and description', function (string $enumClass) {
    $table = $enumClass::getFrontendLookupTable();

    expect($table)->toHaveCount(count($enumClass::cases()));

    foreach ($enumClass::cases() as $case) {
        expect($table)->toHaveKey($case->value)
            ->and($table[$case->value]['label'])->not->toBeEmpty()
            ->and($table[$case->value]['color'])->not->toBeEmpty()
            ->and($table[$case->value]['description'])->not->toBeEmpty();
    }
})->with(domainEnumClasses());

test('reservation stage and bike situation expose expected labels and colors', function () {
    expect(ReservationStage::Provisional->getLabel())->toBe('Provisional')
        ->and(ReservationStage::Provisional->getColor())->toBe('gray')
        ->and(ReservationStage::NoShow->getLabel())->toBe('No Show')
        ->and(BikeSituation::RentedOut->getLabel())->toBe('Rented Out')
        ->and(BikeSituation::RentedOut->getColor())->toBe('red')
        ->and(BikeSituation::Home->getIcon())->toBe('heroicon-o-home');
});
