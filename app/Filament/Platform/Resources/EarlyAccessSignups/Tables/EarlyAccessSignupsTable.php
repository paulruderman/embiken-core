<?php

namespace App\Filament\Platform\Resources\EarlyAccessSignups\Tables;

use App\Actions\Platform\SendEarlyAccessInvitationAction;
use App\Models\EarlyAccessSignup;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EarlyAccessSignupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label(__('app.early_access_admin.email'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('app.early_access_admin.name'))->searchable()->placeholder('—'),
                TextColumn::make('shop_name')->label(__('app.early_access_admin.shop_name'))->searchable()->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('app.early_access_admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('app.early_access_admin.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'invited' => 'success',
                        'closed' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('created_at')->label(__('app.early_access_admin.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('app.early_access_admin.status'))->options([
                    'new' => __('app.early_access_admin.new'),
                    'invited' => __('app.early_access_admin.invited'),
                    'closed' => __('app.early_access_admin.closed'),
                ]),
            ])
            ->recordActions([
                Action::make('invite')
                    ->label(__('app.early_access_admin.send_invitation'))
                    ->requiresConfirmation()
                    ->visible(fn (EarlyAccessSignup $record): bool => $record->status !== 'closed')
                    ->action(function (EarlyAccessSignup $record): void {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        app(SendEarlyAccessInvitationAction::class)($record, $user);
                    }),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([]);
    }
}
