<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\CustomerMembershipResource\Pages;
use App\Models\CustomerMembership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CustomerMembershipResource extends Resource
{
    protected static ?string $model = CustomerMembership::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Placeholder::make('customer_name')
                            ->label('Customer Name')
                            ->content(fn ($record) => $record->customer->full_name ?? 'N/A'),

                        Forms\Components\Placeholder::make('customer_phone')
                            ->label('Phone Number')
                            ->content(fn ($record) => $record->customer->phone_number ?? 'N/A'),

                        Forms\Components\Placeholder::make('customer_email')
                            ->label('Email')
                            ->content(fn ($record) => $record->customer->email ?? 'N/A'),

                        Forms\Components\Placeholder::make('joined_at')
                            ->label('Member Since')
                            ->content(fn ($record) => $record->joined_at?->format('M d, Y') ?? 'N/A'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Points & Tier')
                    ->schema([
                        Forms\Components\TextInput::make('current_points')
                            ->label('Current Points')
                            ->numeric()
                            ->disabled()
                            ->suffix('points'),

                        Forms\Components\TextInput::make('total_points_earned')
                            ->label('Total Points Earned')
                            ->numeric()
                            ->disabled()
                            ->suffix('points'),

                        Forms\Components\TextInput::make('total_points_redeemed')
                            ->label('Total Points Redeemed')
                            ->numeric()
                            ->disabled()
                            ->suffix('points'),

                        Forms\Components\Select::make('tier_level')
                            ->label('Current Tier')
                            ->disabled()
                            ->options([
                                'bronze' => '🥉 Bronze',
                                'silver' => '🥈 Silver',
                                'gold' => '🥇 Gold',
                                'platinum' => '💎 Platinum',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Activity')
                    ->schema([
                        Forms\Components\TextInput::make('total_visits')
                            ->label('Total Visits')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('total_spent')
                            ->label('Total Spent')
                            ->numeric()
                            ->disabled()
                            ->suffix('JOD'),

                        Forms\Components\Placeholder::make('last_visit_at')
                            ->label('Last Visit')
                            ->content(fn ($record) => $record->last_visit_at?->diffForHumans() ?? 'Never'),

                        Forms\Components\Select::make('membership_status')
                            ->label('Status')
                            ->options([
                                'active' => '✅ Active',
                                'suspended' => '⏸️ Suspended',
                                'blocked' => '🚫 Blocked',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable(['global_customers.full_name', 'global_customers.phone_number'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer.phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('current_points')
                    ->label('Points')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->suffix(' pts')
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('tier_level')
                    ->label('Tier')
                    ->sortable()
                    ->colors([
                        'secondary' => 'bronze',
                        'info' => 'silver',
                        'warning' => 'gold',
                        'success' => 'platinum',
                    ])
                    ->icons([
                        'heroicon-o-trophy' => fn ($state) => true,
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('total_visits')
                    ->label('Visits')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->suffix(' JOD')
                    ->color('success'),

                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state->isAfter(now()->subDays(7)) ? 'success' : 'gray'),

                Tables\Columns\BadgeColumn::make('membership_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'blocked',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier_level')
                    ->label('Tier')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),

                Tables\Filters\SelectFilter::make('membership_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'blocked' => 'Blocked',
                    ]),

                Tables\Filters\Filter::make('inactive')
                    ->query(fn ($query) => $query->where('last_visit_at', '<', now()->subDays(30)))
                    ->label('Inactive (30+ days)'),

                Tables\Filters\Filter::make('high_value')
                    ->query(fn ($query) => $query->where('total_spent', '>', 500))
                    ->label('High Value (>500 JOD)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for customers
            ])
            ->defaultSort('last_visit_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.full_name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('customer.phone_number')
                            ->label('Phone'),
                        Infolists\Components\TextEntry::make('customer.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('customer.date_of_birth')
                            ->label('Birthday')
                            ->date('M d, Y'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Membership Stats')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_points')
                            ->label('Current Points')
                            ->suffix(' points')
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('tier_level')
                            ->label('Tier')
                            ->badge(),
                        Infolists\Components\TextEntry::make('total_visits')
                            ->label('Total Visits'),
                        Infolists\Components\TextEntry::make('total_spent')
                            ->label('Total Spent')
                            ->suffix(' JOD'),
                        Infolists\Components\TextEntry::make('joined_at')
                            ->label('Member Since')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('last_visit_at')
                            ->label('Last Visit')
                            ->dateTime('M d, Y h:i A'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerMemberships::route('/'),
            'view' => Pages\ViewCustomerMembership::route('/{record}'),
            'edit' => Pages\EditCustomerMembership::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('membership_status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
