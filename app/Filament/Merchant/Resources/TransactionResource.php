<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Placeholder::make('customer_name')
                            ->label('Customer')
                            ->content(fn ($record) => $record->customerMembership->customer->full_name ?? 'N/A'),

                        Forms\Components\Placeholder::make('type_label')
                            ->label('Transaction Type')
                            ->content(fn ($record) => $record->getTypeLabelEn()),

                        Forms\Components\Placeholder::make('points')
                            ->label('Points')
                            ->content(fn ($record) => ($record->points > 0 ? '+' : '') . number_format($record->points)),

                        Forms\Components\Placeholder::make('amount')
                            ->label('Purchase Amount')
                            ->content(fn ($record) => $record->amount ? number_format($record->amount, 2) . ' JOD' : 'N/A'),

                        Forms\Components\Placeholder::make('description')
                            ->label('Description')
                            ->content(fn ($record) => $record->description ?? 'N/A'),

                        Forms\Components\Placeholder::make('balance_after')
                            ->label('Balance After')
                            ->content(fn ($record) => number_format($record->balance_after) . ' points'),

                        Forms\Components\Placeholder::make('staff_name')
                            ->label('Processed By')
                            ->content(fn ($record) => $record->staff?->full_name ?? 'System'),

                        Forms\Components\Placeholder::make('created_at')
                            ->label('Date & Time')
                            ->content(fn ($record) => $record->created_at?->format('M d, Y h:i A')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customerMembership.customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'earn',
                        'danger' => 'redeem',
                        'info' => 'bonus',
                        'warning' => 'referral',
                        'secondary' => 'manual_add',
                        'secondary' => 'manual_subtract',
                        'gray' => 'expire',
                    ])
                    ->icons([
                        'heroicon-o-arrow-trending-up' => 'earn',
                        'heroicon-o-arrow-trending-down' => 'redeem',
                        'heroicon-o-gift' => 'bonus',
                        'heroicon-o-user-plus' => 'referral',
                        'heroicon-o-plus' => 'manual_add',
                        'heroicon-o-minus' => 'manual_subtract',
                    ])
                    ->formatStateUsing(fn ($state, $record) => $record->getTypeLabelEn()),

                Tables\Columns\TextColumn::make('points')
                    ->label('Points')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' JOD' : '-')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->suffix(' pts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('staff.full_name')
                    ->label('Staff')
                    ->placeholder('System')
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'earn' => 'Points Earned',
                        'redeem' => 'Points Redeemed',
                        'bonus' => 'Bonus Points',
                        'referral' => 'Referral Points',
                        'manual_add' => 'Manual Addition',
                        'manual_subtract' => 'Manual Subtraction',
                        'expire' => 'Points Expired',
                    ]),

                Tables\Filters\Filter::make('credits')
                    ->query(fn ($query) => $query->where('points', '>', 0))
                    ->label('Credits Only (Points Added)'),

                Tables\Filters\Filter::make('debits')
                    ->query(fn ($query) => $query->where('points', '<', 0))
                    ->label('Debits Only (Points Deducted)'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->label('Date Range'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for transactions
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Transactions are created automatically - no manual creation
        return false;
    }
}
