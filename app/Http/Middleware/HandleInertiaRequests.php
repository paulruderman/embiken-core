<?php

namespace App\Http\Middleware;

use App\Enums\BikeAssignmentPolicy;
use App\Enums\BikeReservationStatus;
use App\Enums\BikeSituation;
use App\Enums\ConfirmThreshold;
use App\Enums\PackageMeter;
use App\Enums\ReservationChannel;
use App\Enums\ReservationStage;
use App\Enums\ReturnSituation;
use App\Enums\ServiceStage;
use App\Enums\StaffRole;
use App\Enums\TransactionKind;
use App\Enums\TransactionStatus;
use App\Enums\Weekday;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @var array<int, string>
     */
    protected $withoutSsr = [
        'prototype/terminal',
    ];

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'enums' => [
                'reservationStage' => ReservationStage::getFrontendLookupTable(),
                'bikeSituation' => BikeSituation::getFrontendLookupTable(),
                'bikeReservationStatus' => BikeReservationStatus::getFrontendLookupTable(),
                'packageMeter' => PackageMeter::getFrontendLookupTable(),
                'confirmThreshold' => ConfirmThreshold::getFrontendLookupTable(),
                'bikeAssignmentPolicy' => BikeAssignmentPolicy::getFrontendLookupTable(),
                'returnSituation' => ReturnSituation::getFrontendLookupTable(),
                'reservationChannel' => ReservationChannel::getFrontendLookupTable(),
                'staffRole' => StaffRole::getFrontendLookupTable(),
                'transactionKind' => TransactionKind::getFrontendLookupTable(),
                'transactionStatus' => TransactionStatus::getFrontendLookupTable(),
                'serviceStage' => ServiceStage::getFrontendLookupTable(),
                'weekday' => Weekday::getFrontendLookupTable(),
            ],
        ];
    }
}
