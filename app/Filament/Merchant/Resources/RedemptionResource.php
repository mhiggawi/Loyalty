<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\RedemptionResource\Pages;
use App\Models\Redemption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class RedemptionResource extends Resource
{
    protected static ?string $model = Redemption::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Redemptions';

    protected static ?string $navigationGroup = 'Rewards & Tiers';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Redemption Details')
                    ->schema([
                        Forms\Components\Placeholder::make('redemption_code')
                            ->label('Redemption Code')
                            ->content(fn ($record) => $record->redemption_code ?? 'N/A'),

                        Forms\Components\Placeholder::make('customer_name')
                            ->label('Customer')
                            ->content(fn ($record) => $record->customerMembership->customer->full_name ?? 'N/A'),

                        Forms\Components\Placeholder::make('reward_name')
                            ->label('Reward')
                            ->content(fn ($record) => $record->reward->title_ar ?? 'N/A'),

                        Forms\Components\Placeholder::make('points_used')
                            ->label('Points Used')
                            ->content(fn ($record) => number_format($record->points_used) . ' points'),

                        Forms\Components\Placeholder::make('redeemed_at')
                            ->label('Redeemed On')
                            ->content(fn ($record) => $record->redeemed_at?->format('M d, Y h:i A') ?? 'N/A'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status Management')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => '⏳ Pending',
                                'approved' => '✅ Approved',
                                'rejected' => '❌ Rejected',
                                'used' => '✔️ Used',
                                'expired' => '⌛ Expired',
                                'cancelled' => '🚫 Cancelled',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Add rejection reason or other notes...'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\Placeholder::make('approved_at')
                            ->label('Approved At')
                            ->content(fn ($record) => $record->approved_at?->format('M d, Y h:i A') ?? 'Not approved yet'),

                        Forms\Components\Placeholder::make('used_at')
                            ->label('Used At')
                            ->content(fn ($record) => $record->used_at?->format('M d, Y h:i A') ?? 'Not used yet'),

                        Forms\Components\Placeholder::make('approver_name')
                            ->label('Approved By')
                            ->content(fn ($record) => $record->approver?->full_name ?? 'N/A'),

                        Forms\Components\Placeholder::make('user_name')
                            ->label('Used By')
                            ->content(fn ($record) => $record->user?->full_name ?? 'N/A'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('redemption_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customerMembership.customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reward.title_ar')
                    ->label('Reward')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('points_used')
                    ->label('Points')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->suffix(' pts')
                    ->color('danger'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'used',
                        'secondary' => 'expired',
                        'secondary' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-check-badge' => 'used',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('redeemed_at')
                    ->label('Redeemed')
                    ->dateTime('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Approved By')
                    ->placeholder('Pending')
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'used' => 'Used',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('needs_approval')
                    ->query(fn ($query) => $query->where('status', 'pending'))
                    ->label('Needs Approval')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->approve(auth('staff')->id());
                        Notification::make()
                            ->title('Redemption Approved')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject(auth('staff')->id(), $data['reason']);
                        Notification::make()
                            ->title('Redemption Rejected')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_used')
                    ->label('Mark as Used')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->action(function ($record) {
                        $record->markAsUsed(auth('staff')->id());
                        Notification::make()
                            ->title('Marked as Used')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for redemptions
            ])
            ->defaultSort('redeemed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedemptions::route('/'),
            'view' => Pages\ViewRedemption::route('/{record}'),
            'edit' => Pages\EditRedemption::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? 'warning' : 'success';
    }
}
