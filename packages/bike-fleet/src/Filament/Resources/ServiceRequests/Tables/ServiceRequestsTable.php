<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Tables;

use App\Enums\StaffRole;
use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Filament\Tables\Columns\LocalizedDateTimeColumn;
use App\Models\Staff;
use Embiken\BikeFleet\Actions\AssignServiceRequestToMeAction;
use Embiken\BikeFleet\Actions\ResolveServiceRequestsAction;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Models\ServiceRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('bike.bid')
                    ->label(__('app.fleet.fields.bike'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('app.fleet.fields.description'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state !== null && mb_strlen($state) > 40 ? $state : null)
                    ->searchable(),
                TextColumn::make('latestEntry.notes')
                    ->label(__('app.fleet.fields.latest_work'))
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state !== null && mb_strlen($state) > 40 ? $state : null)
                    ->placeholder(__('app.fleet.table.no_work_logged')),
                LocalizedDateTimeColumn::make('latestEntry.created_at')
                    ->label(__('app.fleet.fields.last_worked'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('stage')
                    ->label(__('app.fleet.fields.stage'))
                    ->badge()
                    ->color(fn (ServiceRequest $record): string => $record->stage->getColor()),
                IconColumn::make('blocks_usage')
                    ->label(__('app.fleet.fields.blocks_usage'))
                    ->boolean(),
                TextColumn::make('assignedTo.name')
                    ->label(__('app.fleet.fields.assigned_to'))
                    ->sortable()
                    ->searchable()
                    ->placeholder(__('app.fleet.table.unassigned')),
                LocalizedDateTimeColumn::make('resolved_at')
                    ->label(__('app.fleet.fields.resolved_at'))
                    ->sortable()
                    ->placeholder(__('app.fleet.table.not_resolved')),
                TextColumn::make('resolvedBy.name')
                    ->label(__('app.fleet.fields.resolved_by'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->options(ServiceStage::class)
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('blocks_usage')
                    ->label(__('app.fleet.filters.blocks_bike_use'))
                    ->trueLabel(__('app.fleet.filters.blocks_use'))
                    ->falseLabel(__('app.fleet.filters.does_not_block_use')),
                TernaryFilter::make('assignment')
                    ->label(__('app.fleet.filters.assignment'))
                    ->placeholder(__('app.fleet.filters.all_requests'))
                    ->trueLabel(__('app.fleet.table.assigned'))
                    ->falseLabel(__('app.fleet.table.unassigned'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('assigned_to'),
                        false: fn (Builder $query): Builder => $query->whereNull('assigned_to'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'assignedTo',
                'bike',
                'latestEntry',
                'resolvedBy',
            ]))
            ->recordActions([
                Action::make('assignToMe')
                    ->label(__('app.fleet.actions.assign_to_me'))
                    ->icon('heroicon-o-user-circle')
                    ->authorize(fn (): bool => Auth::guard('staff')->user() instanceof Staff
                        && Auth::guard('staff')->user()->role === StaffRole::Manager
                        && ! Auth::guard('staff')->user()->is_platform_manager)
                    ->visible(fn (ServiceRequest $record): bool => $record->stage->occupiesWhenBlocking()
                        && ! Auth::guard('staff')->user()?->is_platform_manager
                        && (int) $record->assigned_to !== (int) Auth::guard('staff')->id())
                    ->action(function (ServiceRequest $record): void {
                        $staff = Auth::guard('staff')->user();

                        abort_unless($staff instanceof Staff, 403);

                        app(AssignServiceRequestToMeAction::class)($record, $staff);

                        Notification::make()
                            ->title(__('app.fleet.notifications.assigned_to_you'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resolveSelected')
                        ->label(__('app.fleet.actions.resolve_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => Auth::guard('staff')->user() instanceof Staff
                            && Auth::guard('staff')->user()->role === StaffRole::Manager
                            && ! Auth::guard('staff')->user()->is_platform_manager)
                        ->action(function (Collection $records): void {
                            $staff = Auth::guard('staff')->user();

                            abort_unless($staff instanceof Staff, 403);

                            $resolved = app(ResolveServiceRequestsAction::class)(
                                $records->modelKeys(),
                                $staff,
                            );

                            Notification::make()
                                ->title(trans_choice('app.fleet.notifications.resolved_count', count($resolved), ['count' => count($resolved)]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::ServiceRequests,
            __('app.fleet.empty_states.service_requests'),
        );
    }
}
