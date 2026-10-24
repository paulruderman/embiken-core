<?php

namespace Embiken\BikeFleet\Filament\Resources\ServiceRequests\Tables;

use App\Enums\StaffRole;
use App\Filament\Shop\Support\ShopSetupReadiness;
use App\Models\Staff;
use Embiken\BikeFleet\Actions\AssignServiceRequestToMeAction;
use Embiken\BikeFleet\Actions\ResolveServiceRequestsAction;
use Embiken\BikeFleet\Enums\ServiceStage;
use Embiken\BikeFleet\Models\ServiceRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                    ->label('Bike')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('latestEntry.notes')
                    ->label('Latest work')
                    ->limit(40)
                    ->placeholder('No work logged yet'),
                TextColumn::make('latestEntry.created_at')
                    ->label('Last worked')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('stage')
                    ->badge(),
                IconColumn::make('blocks_usage')
                    ->boolean(),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned to')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned'),
                TextColumn::make('resolved_at')
                    ->label('Resolved at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not resolved'),
                TextColumn::make('resolvedBy.name')
                    ->label('Resolved by')
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
                    ->label('Blocks bike use')
                    ->trueLabel('Blocks use')
                    ->falseLabel('Does not block use'),
                TernaryFilter::make('assignment')
                    ->label('Assignment')
                    ->placeholder('All requests')
                    ->trueLabel('Assigned')
                    ->falseLabel('Unassigned')
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
                    ->label('Assign to me')
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
                            ->title('Service request assigned to you')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resolveSelected')
                        ->label('Resolve selected')
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
                                ->title(count($resolved).' service request(s) resolved')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);

        return app(ShopSetupReadiness::class)->configureEmptyState(
            $table,
            ShopSetupReadiness::ServiceRequests,
            'No service requests yet',
        );
    }
}
