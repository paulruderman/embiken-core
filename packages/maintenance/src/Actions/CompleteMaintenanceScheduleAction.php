<?php

declare(strict_types=1);

namespace Embiken\Maintenance\Actions;

use App\Actions\Action;
use App\Actions\Concerns\AuthorizesStaff;
use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Http\Resources\ServiceRequestResource;
use Embiken\BikeFleet\Models\ServiceEntry;
use Embiken\BikeFleet\Models\ServiceRequest;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteMaintenanceScheduleAction extends Action
{
    use AuthorizesStaff;

    public string $commandSignature = 'maintenance:schedules:complete {schedule?} {--staff=} {--notes=} {--labor-minutes=} {--tenant=}';

    /**
     * @param  array<int, array{label: string, completed: bool}>  $checklistItems
     */
    public function handle(
        MaintenanceSchedule $schedule,
        Staff $staff,
        array $checklistItems,
        string $notes,
        ?int $laborMinutes = null,
    ): ServiceRequest {
        if ($staff->role !== StaffRole::Manager || $staff->is_platform_manager) {
            throw ValidationException::withMessages(['staff' => 'A shop Manager must complete maintenance schedules.']);
        }

        if (trim($notes) === '') {
            throw ValidationException::withMessages(['notes' => 'Work notes are required.']);
        }

        return DB::transaction(function () use ($schedule, $staff, $checklistItems, $notes, $laborMinutes): ServiceRequest {
            $schedule = MaintenanceSchedule::query()->whereKey($schedule->getKey())->lockForUpdate()->firstOrFail();
            $checklistItems = array_values($checklistItems);

            if ($schedule->status !== 'scheduled' || $schedule->service_request_id !== null) {
                throw ValidationException::withMessages(['schedule' => 'Only an unfinished scheduled maintenance item can be completed.']);
            }

            $storedItems = $schedule->checklist_items ?? [];
            if (count($storedItems) !== count($checklistItems)) {
                throw ValidationException::withMessages(['checklist_items' => 'The submitted checklist does not match this schedule.']);
            }

            foreach ($storedItems as $index => $item) {
                if (($checklistItems[$index]['label'] ?? null) !== $item['label']) {
                    throw ValidationException::withMessages(['checklist_items' => 'The submitted checklist does not match this schedule.']);
                }

                if ($checklistItems[$index]['completed'] !== true) {
                    throw ValidationException::withMessages(['checklist_items' => 'Complete every checklist item before finishing this work.']);
                }
            }

            $now = now();
            $serviceRequest = ServiceRequest::query()->create([
                'bike_id' => $schedule->bike_id,
                'description' => 'Scheduled maintenance: '.$schedule->title,
                'stage' => ServiceStage::Resolved,
                'blocks_usage' => false,
                'created_by' => $staff->getKey(),
                'resolved_by' => $staff->getKey(),
                'resolved_at' => $now,
            ]);

            ServiceEntry::query()->create([
                'service_request_id' => $serviceRequest->getKey(),
                'staff_id' => $staff->getKey(),
                'notes' => trim($notes),
                'labor_minutes' => $laborMinutes,
                'work_completed_at' => $now,
            ]);

            $schedule->forceFill([
                'status' => 'completed',
                'checklist_items' => $checklistItems,
                'service_request_id' => $serviceRequest->getKey(),
                'completed_by' => $staff->getKey(),
                'completed_at' => $now,
            ])->save();

            return $serviceRequest->refresh();
        }, attempts: 5);
    }

    public function asController(Request $request, MaintenanceSchedule $maintenanceSchedule): ServiceRequest
    {
        $staff = Auth::guard('staff')->user();
        abort_unless($staff instanceof Staff, 403);

        $validated = $request->validate([
            'checklist_items' => ['present', 'array'],
            'checklist_items.*.label' => ['required', 'string', 'max:255'],
            'checklist_items.*.completed' => ['required', 'boolean'],
            'notes' => ['required', 'string'],
            'labor_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        return $this($maintenanceSchedule, $staff, $validated['checklist_items'], $validated['notes'], $validated['labor_minutes'] ?? null);
    }

    public function jsonResponse(ServiceRequest $serviceRequest): ServiceRequestResource
    {
        return new ServiceRequestResource($serviceRequest);
    }

    public static function routes(Router $router): void
    {
        $router->post('/maintenance-schedules/{maintenanceSchedule}/complete', static::class)
            ->name('maintenance-schedules.complete');
    }

    public function asCommand(Command $command): int
    {
        $this->initializeTenancyFromCommand($command);
        $scheduleId = $this->requiredStringInput($command, $command->argument('schedule'), 'Schedule id');
        $schedule = MaintenanceSchedule::query()->findOrFail($scheduleId);
        $staffId = $command->option('staff');

        if (! is_string($staffId) || $staffId === '') {
            $staffOptions = Staff::query()->where('role', StaffRole::Manager->value)->where('is_platform_manager', false)->orderBy('name')->pluck('name', 'id')->all();
            if ($staffOptions === [] || $command->option('no-interaction')) {
                throw ValidationException::withMessages(['staff' => 'Use --staff to select a shop Manager.']);
            }
            $staffId = (string) \Laravel\Prompts\select('Shop Manager', $staffOptions);
        }

        $staff = Staff::query()->where('role', StaffRole::Manager->value)->where('is_platform_manager', false)->findOrFail($staffId);
        $notes = $this->requiredStringInput($command, $command->option('notes'), 'Work notes');
        $laborMinutes = $command->option('labor-minutes');
        $items = $schedule->checklist_items ?? [];

        if ($command->option('no-interaction') && collect($items)->contains(fn (array $item): bool => ! $item['completed'])) {
            throw ValidationException::withMessages(['checklist_items' => 'Mark every checklist item complete before using --no-interaction.']);
        }

        foreach ($items as $index => $item) {
            if (! $item['completed']) {
                $items[$index]['completed'] = \Laravel\Prompts\confirm('Complete '.$item['label'].'?', default: false);
            }
        }

        $serviceRequest = $this($schedule, $staff, $items, $notes, is_numeric($laborMinutes) ? (int) $laborMinutes : null);
        $command->info("Maintenance schedule {$scheduleId} completed as service request {$serviceRequest->getKey()}.");

        return self::SUCCESS;
    }
}
