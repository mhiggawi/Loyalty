<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\PointsSettingResource\Pages;
use App\Models\PointsSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class PointsSettingResource extends Resource
{
    protected static ?string $model = PointsSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Points Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Points Configuration')
                    ->description('Configure how customers earn points')
                    ->schema([
                        Forms\Components\TextInput::make('currency_to_points_ratio')
                            ->label('Currency to Points Ratio')
                            ->helperText('Example: 10 means 1 JOD = 10 points')
                            ->numeric()
                            ->required()
                            ->default(1.00)
                            ->minValue(0.01)
                            ->suffix('points per 1 JOD'),

                        Forms\Components\TextInput::make('points_expiry_months')
                            ->label('Points Expiry (months)')
                            ->helperText('Leave empty for no expiry')
                            ->numeric()
                            ->nullable()
                            ->suffix('months'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Redemption Settings')
                    ->schema([
                        Forms\Components\Toggle::make('allow_partial_redemption')
                            ->label('Allow Partial Redemption')
                            ->helperText('Allow customers to use part of their points')
                            ->default(true),

                        Forms\Components\TextInput::make('min_points_for_redemption')
                            ->label('Minimum Points for Redemption')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bonus Points')
                    ->schema([
                        Forms\Components\TextInput::make('welcome_bonus_points')
                            ->label('Welcome Bonus')
                            ->helperText('Points given when customer joins')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('points'),

                        Forms\Components\TextInput::make('birthday_bonus_points')
                            ->label('Birthday Bonus')
                            ->helperText('Points given on customer birthday')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('points'),

                        Forms\Components\TextInput::make('referrer_bonus_points')
                            ->label('Referrer Bonus')
                            ->helperText('Points for customer who refers')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('points'),

                        Forms\Components\TextInput::make('referee_bonus_points')
                            ->label('Referee Bonus')
                            ->helperText('Points for new referred customer')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('points'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency_to_points_ratio')
                    ->label('Points Ratio')
                    ->formatStateUsing(fn ($state) => "1 JOD = {$state} points"),

                Tables\Columns\TextColumn::make('points_expiry_months')
                    ->label('Points Expiry')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} months" : 'Never expires')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'success'),

                Tables\Columns\IconColumn::make('allow_partial_redemption')
                    ->label('Partial Redemption')
                    ->boolean(),

                Tables\Columns\TextColumn::make('min_points_for_redemption')
                    ->label('Min Points')
                    ->suffix(' points'),

                Tables\Columns\TextColumn::make('welcome_bonus_points')
                    ->label('Welcome Bonus')
                    ->suffix(' pts'),

                Tables\Columns\TextColumn::make('birthday_bonus_points')
                    ->label('Birthday Bonus')
                    ->suffix(' pts'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePointsSettings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        // Only one points setting per tenant
        $tenant = Filament::getTenant();
        return $tenant && !PointsSetting::where('tenant_id', $tenant->id)->exists();
    }
}
