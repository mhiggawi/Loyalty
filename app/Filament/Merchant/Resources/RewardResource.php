<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\RewardResource\Pages;
use App\Models\Reward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Rewards';

    protected static ?string $navigationGroup = 'Rewards & Tiers';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Details')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('Title (Arabic)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('اسم المكافأة بالعربي'),

                        Forms\Components\TextInput::make('title_en')
                            ->label('Title (English)')
                            ->maxLength(255)
                            ->placeholder('Reward name in English'),

                        Forms\Components\Textarea::make('description_ar')
                            ->label('Description (Arabic)')
                            ->rows(3)
                            ->placeholder('وصف المكافأة بالعربي'),

                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->placeholder('Reward description in English'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Reward Type & Value')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->required()
                            ->options([
                                'drink' => '☕ Drink',
                                'food' => '🍔 Food',
                                'discount' => '💰 Discount',
                                'gift' => '🎁 Gift',
                                'experience' => '✨ Experience',
                                'service' => '🛠️ Service',
                                'other' => '📦 Other',
                            ]),

                        Forms\Components\Select::make('reward_type')
                            ->label('Reward Type')
                            ->required()
                            ->options([
                                'free_product' => 'Free Product',
                                'percentage_discount' => 'Percentage Discount',
                                'fixed_discount' => 'Fixed Amount Discount',
                                'experience' => 'Special Experience',
                            ])
                            ->reactive(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label('Discount Value')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn ($get) => in_array($get('reward_type'), ['percentage_discount', 'fixed_discount']))
                            ->helperText(fn ($get) => $get('reward_type') === 'percentage_discount' ? 'Enter percentage (e.g., 10 for 10%)' : 'Enter amount in JOD')
                            ->suffix(fn ($get) => $get('reward_type') === 'percentage_discount' ? '%' : 'JOD'),

                        Forms\Components\TextInput::make('points_required')
                            ->label('Points Required')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('points')
                            ->helperText('How many points needed to redeem this reward'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock & Availability')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Leave empty for unlimited stock'),

                        Forms\Components\Select::make('min_tier_required')
                            ->label('Minimum Tier Required')
                            ->nullable()
                            ->options([
                                'bronze' => '🥉 Bronze',
                                'silver' => '🥈 Silver',
                                'gold' => '🥇 Gold',
                                'platinum' => '💎 Platinum',
                            ])
                            ->helperText('Only customers at this tier or higher can redeem'),

                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Valid From')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Valid Until')
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive rewards won\'t appear in the catalog'),

                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Reward Image')
                            ->image()
                            ->directory('rewards')
                            ->maxSize(2048)
                            ->helperText('Recommended: 800x600px, max 2MB'),
                    ]),

                Forms\Components\Section::make('Terms & Conditions')
                    ->schema([
                        Forms\Components\Textarea::make('terms_ar')
                            ->label('Terms (Arabic)')
                            ->rows(2)
                            ->placeholder('الشروط والأحكام بالعربي'),

                        Forms\Components\Textarea::make('terms_en')
                            ->label('Terms (English)')
                            ->rows(2)
                            ->placeholder('Terms and conditions in English'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular(),

                Tables\Columns\TextColumn::make('title_ar')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'info' => 'drink',
                        'warning' => 'food',
                        'success' => 'discount',
                        'danger' => 'gift',
                        'secondary' => 'experience',
                    ])
                    ->icons([
                        'heroicon-o-beaker' => 'drink',
                        'heroicon-o-cake' => 'food',
                        'heroicon-o-currency-dollar' => 'discount',
                        'heroicon-o-gift' => 'gift',
                        'heroicon-o-sparkles' => 'experience',
                    ]),

                Tables\Columns\TextColumn::make('reward_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'free_product' => 'Free Product',
                        'percentage_discount' => 'Percentage Off',
                        'fixed_discount' => 'Fixed Discount',
                        'experience' => 'Experience',
                        default => $state,
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('points_required')
                    ->label('Points')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->suffix(' pts')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞ Unlimited' : $state)
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'success' : ($state > 0 ? 'warning' : 'danger')),

                Tables\Columns\BadgeColumn::make('min_tier_required')
                    ->label('Min Tier')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'All')
                    ->colors([
                        'secondary' => 'bronze',
                        'info' => 'silver',
                        'warning' => 'gold',
                        'success' => 'platinum',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('total_redemptions')
                    ->label('Redeemed')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'drink' => 'Drink',
                        'food' => 'Food',
                        'discount' => 'Discount',
                        'gift' => 'Gift',
                        'experience' => 'Experience',
                        'service' => 'Service',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('reward_type')
                    ->options([
                        'free_product' => 'Free Product',
                        'percentage_discount' => 'Percentage Discount',
                        'fixed_discount' => 'Fixed Discount',
                        'experience' => 'Experience',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All rewards')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('in_stock')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('stock')->orWhere('stock', '>', 0);
                    }))
                    ->label('In Stock'),
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
            ->defaultSort('display_order', 'asc')
            ->reorderable('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            'edit' => Pages\EditReward::route('/{record}/edit'),
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
