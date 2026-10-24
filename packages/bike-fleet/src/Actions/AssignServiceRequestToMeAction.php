<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Models\Staff;
use Embiken\BikeFleet\Http\Resources\ServiceRequestResource;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignServiceRequestToMeAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'service-requests:assign-to-me {service_request?} {--staff=} {--tenant=}';

    public function handle(ServiceRequest $serviceRequest, Staff $staff): ServiceRequest
    {
        if ($staff->is_platform_manager) {
            throw ValidationException::withMessages(['staff' => 'The Platform Manager cannot be assigned to service requests.']);
        }

        return DB::transaction(function () use ($serviceRequest, $staff): ServiceRequest {
            app(FleetMutationLock::class)->lockBikeForMutation($serviceRequest->bike);

            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $serviceRequest->stage->occupiesWhenBlocking()) {
                throw ValidationException::withMessages(['service_request' => 'Only active service requests can be assigned.']);
            }

            $serviceRequest->assigned_to = $staff->getKey();
            $serviceRequest->save();

            return $serviceRequest->refresh();
        }, attempts: 5);
    }

    public function asController(Request $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        $staff = Auth::guard('staff')->user();

        abort_unless($staff instanceof Staff, 403);

        return new ServiceRequestResource($this($serviceRequest, $staff));
    }

    public static function routes(Router $router): void
    {
        $router->post('/service-requests/{serviceRequest}/assign-to-me', static::class)
            ->name('service-requests.assign-to-me');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $serviceRequestId = $this->requiredStringInput(
            $command,
            $command->argument('service_request'),
            'Service request id',
        );
        $staffId = $command->option('staff');

        if (! is_string($staffId) || $staffId === '') {
            $staffOptions = Staff::query()
                ->where('is_platform_manager', false)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

            if ($staffOptions === []) {
                throw ValidationException::withMessages(['staff' => 'No assignable staff members exist.']);
            }

            if ($command->option('no-interaction')) {
                throw ValidationException::withMessages(['staff' => 'The --staff option is required.']);
            }

            $staffId = (string) \Laravel\Prompts\select('Assign to staff member', $staffOptions);
        }

        $serviceRequest = $this->findOneOrFail(ServiceRequest::query(), $serviceRequestId);
        $staff = Staff::query()->where('is_platform_manager', false)->findOrFail($staffId);

        $this($serviceRequest, $staff);
        $command->info("Service request {$serviceRequestId} assigned to {$staff->name}.");

        return self::SUCCESS;
    }
}
