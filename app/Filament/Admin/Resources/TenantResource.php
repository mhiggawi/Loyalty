<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Merchants';

    protected static ?string $navigationGroup = 'Platform Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Business Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('business_slug')
                            ->label('Subdomain Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Used for subdomain: [slug].yourdomain.com')
                            ->regex('/^[a-z0-9-]+$/'),

                        Forms\Components\Select::make('business_type')
                            ->label('Business Type')
                            ->required()
                            ->options([
                                'restaurant' => 'Restaurant',
                                'salon' => 'Salon',
                                'retail' => 'Retail',
                                'gym' => 'Gym',
                                'cafe' => 'Cafe',
                                'other' => 'Other',
                            ]),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->directory('tenant-logos')
                            ->maxSize(2048),

                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->default('#667eea'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Select::make('subscription_plan')
                            ->label('Plan')
                            ->required()
                            ->options([
                                'free_trial' => '🎁 Free Trial',
                                'starter' => '🌱 Starter - 49 JOD/month',
                                'professional' => '⭐ Professional - 99 JOD/month',
                                'enterprise' => '💎 Enterprise - 199 JOD/month',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Auto-set limits based on plan
                                match ($state) {
                                    'free_trial' => [
                                        $set('max_customers', 50),
                                        $set('max_staff', 2),
                                    ],
                                    'starter' => [
                                        $set('max_customers', 500),
                                        $set('max_staff', 3),
                                    ],
                                    'professional' => [
                                        $set('max_customers', 2000),
                                        $set('max_staff', 10),
                                    ],
                                    'enterprise' => [
                                        $set('max_customers', 999999),
                                        $set('max_staff', 999999),
                                    ],
                                    default => null,
                                };
                            }),

                        Forms\Components\Select::make('subscription_status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'trial' => '🆓 Trial',
                                'active' => '✅ Active',
                                'suspended' => '⏸️ Suspended',
                                'cancelled' => '❌ Cancelled',
                            ]),

                        Forms\Components\DateTimePicker::make('subscription_expires_at')
                            ->label('Subscription Expires')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->nullable(),

                        Forms\Components\TextInput::make('max_customers')
                            ->label('Max Customers')
                            ->numeric()
                            ->required()
                            ->default(500),

                        Forms\Components\TextInput::make('max_staff')
                            ->label('Max Staff')
                            ->numeric()
                            ->required()
                            ->default(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('API Access')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->disabled()
                            ->helperText('Auto-generated on creation'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('business_slug')
                    ->label('Subdomain')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->formatStateUsing(fn ($state) => "{$state}.domain.com"),

                Tables\Columns\BadgeColumn::make('subscription_plan')
                    ->label('Plan')
                    ->colors([
                        'secondary' => 'free_trial',
                        'success' => 'starter',
                        'warning' => 'professional',
                        'danger' => 'enterprise',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'free_trial' => 'Free Trial',
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('subscription_status')
                    ->label('Status')
                    ->colors([
                        'info' => 'trial',
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('customerMemberships_count')
                    ->label('Customers')
                    ->counts('customerMemberships')
                    ->sortable(),

                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Staff')
                    ->counts('staff')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->options([
                        'free_trial' => 'Free Trial',
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('subscription_status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
