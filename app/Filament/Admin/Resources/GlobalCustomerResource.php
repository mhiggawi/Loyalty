<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GlobalCustomerResource\Pages;
use App\Models\GlobalCustomer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GlobalCustomerResource extends Resource
{
    protected static ?string $model = GlobalCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Global Customers';

    protected static ?string $navigationGroup = 'Platform Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth'),

                        Forms\Components\Select::make('language')
                            ->label('Preferred Language')
                            ->options([
                                'ar' => 'العربية (Arabic)',
                                'en' => 'English',
                            ])
                            ->default('ar'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Verification Status')
                    ->schema([
                        Forms\Components\Placeholder::make('email_verified_at')
                            ->label('Email Verified')
                            ->content(fn ($record) => $record?->email_verified_at ? '✅ Verified' : '❌ Not Verified'),

                        Forms\Components\Placeholder::make('phone_verified_at')
                            ->label('Phone Verified')
                            ->content(fn ($record) => $record?->phone_verified_at ? '✅ Verified' : '❌ Not Verified'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('phone_verified_at')
                    ->label('Phone Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('memberships_count')
                    ->label('Memberships')
                    ->counts('memberships')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('verified')
                    ->query(fn ($query) => $query->whereNotNull('email_verified_at')),

                Tables\Filters\SelectFilter::make('language')
                    ->options([
                        'ar' => 'Arabic',
                        'en' => 'English',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for customers
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
            'index' => Pages\ListGlobalCustomers::route('/'),
            'view' => Pages\ViewGlobalCustomer::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
