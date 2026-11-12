<?php

namespace Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\Pages;

use App\Enums\StaffRole;
use App\Models\Staff;
use Embiken\BikeFleet\Filament\Resources\ServiceRequests\ServiceRequestResource;
use Embiken\Maintenance\Actions\CompleteMaintenanceScheduleAction;
use Embiken\Maintenance\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use Embiken\Maintenance\Models\MaintenanceSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditMaintenanceSchedule extends EditRecord
{
    protected static string $resource = MaintenanceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label(__('app.maintenance_ui.complete'))
                ->visible(fn (MaintenanceSchedule $record): bool => $record->status === 'scheduled')
                ->schema([
                    Repeater::make('checklist_items')
                        ->label(__('app.maintenance_ui.scheduled_checklist'))
                        ->schema([
                            TextInput::make('label')->disabled()->dehydrated(),
                            Toggle::make('completed')->required(),
                        ])
                        ->columns(2)
                        ->deletable(false)
                        ->addable(false),
                    Textarea::make('notes')->label(__('app.maintenance_ui.work_notes'))->required(),
                    TextInput::make('labor_minutes')->numeric()->minValue(0)->label(__('app.maintenance_ui.labor_minutes')),
                ])
                ->fillForm(fn (MaintenanceSchedule $record): array => [
                    'checklist_items' => $record->checklist_items ?? [],
                ])
                ->action(function (MaintenanceSchedule $record, array $data): void {
                    $staff = Auth::guard('staff')->user();
                    abort_unless($staff instanceof Staff && $staff->role === StaffRole::Manager && ! $staff->is_platform_manager, 403);
                    $serviceRequest = app(CompleteMaintenanceScheduleAction::class)(
                        $record,
                        $staff,
                        $data['checklist_items'] ?? [],
                        $data['notes'],
                        isset($data['labor_minutes']) ? (int) $data['labor_minutes'] : null,
                    );

                    $this->redirect(ServiceRequestResource::getUrl('edit', ['record' => $serviceRequest->getKey()]));
                }),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof MaintenanceSchedule && $record->status === 'completed') {
            return $record;
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
