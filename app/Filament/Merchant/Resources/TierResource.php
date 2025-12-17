<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\TierResource\Pages;
use App\Models\Tier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class TierResource extends Resource
{
    protected static ?string $model = Tier::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'VIP Tiers';

    protected static ?string $navigationGroup = 'Rewards & Tiers';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tier Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tier Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Bronze, Silver, Gold, Platinum'),

                        Forms\Components\Select::make('level')
                            ->label('Tier Level')
                            ->required()
                            ->options([
                                'bronze' => '🥉 Bronze',
                                'silver' => '🥈 Silver',
                                'gold' => '🥇 Gold',
                                'platinum' => '💎 Platinum',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Auto-set icon based on level
                                $icons = [
                                    'bronze' => '🥉',
                                    'silver' => '🥈',
                                    'gold' => '🥇',
                                    'platinum' => '💎',
                                ];
                                $set('icon', $icons[$state] ?? '⭐');

                                // Auto-set color based on level
                                $colors = [
                                    'bronze' => '#CD7F32',
                                    'silver' => '#C0C0C0',
                                    'gold' => '#FFD700',
                                    'platinum' => '#E5E4E2',
                                ];
                                $set('color', $colors[$state] ?? '#667eea');
                            }),

                        Forms\Components\TextInput::make('min_points')
                            ->label('Minimum Points Required')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Customers with this many points will be upgraded to this tier')
                            ->suffix('points'),

                        Forms\Components\TextInput::make('points_multiplier')
                            ->label('Points Multiplier')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(0.1)
                            ->default(1.00)
                            ->helperText('Multiply earned points (1.0 = 1x, 1.5 = 1.5x, 2.0 = 2x)')
                            ->suffix('x'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tier Benefits & Appearance')
                    ->schema([
                        Forms\Components\Textarea::make('benefits')
                            ->label('Tier Benefits')
                            ->helperText('Describe the benefits of this tier')
                            ->rows(3)
                            ->placeholder('e.g., 10% discount, Priority service, Free delivery'),

                        Forms\Components\TextInput::make('icon')
                            ->label('Icon')
                            ->helperText('Emoji or icon representation')
                            ->default('⭐')
                            ->maxLength(50),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Tier Color')
                            ->helperText('Color for badges and displays'),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive tiers won\'t be assigned to customers'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon')
                    ->label('')
                    ->size('xl'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('level')
                    ->label('Level')
                    ->colors([
                        'secondary' => 'bronze',
                        'info' => 'silver',
                        'warning' => 'gold',
                        'success' => 'platinum',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('min_points')
                    ->label('Min Points')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->suffix(' pts'),

                Tables\Columns\TextColumn::make('points_multiplier')
                    ->label('Multiplier')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state}x")
                    ->badge()
                    ->color('success'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All tiers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('min_points', 'asc')
            ->reorderable('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTiers::route('/'),
            'create' => Pages\CreateTier::route('/create'),
            'edit' => Pages\EditTier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
