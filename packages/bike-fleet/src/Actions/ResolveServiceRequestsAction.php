<?php

declare(strict_types=1);

namespace Embiken\BikeFleet\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Models\Staff;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Http\Resources\ServiceRequestResource;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\BikeFleet\Services\FleetMutationLock;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveServiceRequestsAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'service-requests:resolve {service_request_ids?} {--staff=} {--tenant=}';

    /**
     * @param  array<int, int|string>  $serviceRequestIds
     * @return EloquentCollection<int, ServiceRequest>
     */
    public function handle(array $serviceRequestIds, Staff $staff): EloquentCollection
    {
        if ($staff->is_platform_manager) {
            throw ValidationException::withMessages(['staff' => 'The Platform Manager cannot resolve service requests.']);
        }

        $serviceRequestIds = array_values(array_unique(array_map(
            static fn (int|string $serviceRequestId): int => (int) $serviceRequestId,
            $serviceRequestIds,
        )));

        if ($serviceRequestIds === [] || min($serviceRequestIds) < 1) {
            throw ValidationException::withMessages(['service_request_ids' => 'Select at least one valid service request.']);
        }

        return DB::transaction(function () use ($serviceRequestIds, $staff): EloquentCollection {
            $serviceRequests = ServiceRequest::query()
                ->whereKey($serviceRequestIds)
                ->orderBy('id')
                ->get();

            if ($serviceRequests->count() !== count($serviceRequestIds)) {
                throw (new ModelNotFoundException)->setModel(ServiceRequest::class, $serviceRequestIds);
            }

            app(FleetMutationLock::class)->lockBikesForMutation($serviceRequests->pluck('bike_id')->map(
                static fn (int|string $bikeId): int => (int) $bikeId,
            )->all());

            $lockedServiceRequests = ServiceRequest::query()
                ->whereKey($serviceRequestIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $resolvedAt = now();
            $resolvedServiceRequests = new EloquentCollection;

            foreach ($lockedServiceRequests as $serviceRequest) {
                if (! $serviceRequest->stage->occupiesWhenBlocking()) {
                    continue;
                }

                $serviceRequest->stage = ServiceStage::Resolved;
                $serviceRequest->resolved_by = $staff->getKey();
                $serviceRequest->resolved_at = $resolvedAt;
                $serviceRequest->save();
                $resolvedServiceRequests->push($serviceRequest);
            }

            return $resolvedServiceRequests;
        }, attempts: 5);
    }

    public function asController(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate($this->rules());
        $staff = Auth::guard('staff')->user();

        abort_unless($staff instanceof Staff, 403);

        return ServiceRequestResource::collection($this($data['service_request_ids'], $staff));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'service_request_ids' => ['required', 'array', 'min:1'],
            'service_request_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }

    public static function routes(Router $router): void
    {
        $router->post('/service-requests/resolve', static::class)->name('service-requests.resolve');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);

        $idsInput = $this->requiredStringInput(
            $command,
            $command->argument('service_request_ids'),
            'Service request ids (comma-separated)',
        );
        $serviceRequestIds = array_map('intval', array_map('trim', explode(',', $idsInput)));
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

            $staffId = (string) \Laravel\Prompts\select('Resolve as staff member', $staffOptions);
        }

        $staff = Staff::query()->where('is_platform_manager', false)->findOrFail($staffId);
        $resolved = $this($serviceRequestIds, $staff);
        $command->info(count($resolved).' service request(s) resolved.');

        return self::SUCCESS;
    }
}
