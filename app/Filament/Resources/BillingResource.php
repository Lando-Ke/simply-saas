<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingResource\Pages;
use App\Models\Subscription;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillingResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $tenantOwnershipRelationshipName = null;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Billing & Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static ?string $pluralModelLabel = 'Subscriptions';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['super-admin', 'app-admin', 'tenant-admin']);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if ($user->hasRole(['super-admin', 'app-admin'])) {
            return true;
        }
        
        // Tenant admins can only view subscriptions for their tenants
        return $user->tenants()->where('tenants.id', tenant()->id)->exists();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship(
                                'user',
                                'name',
                                fn (Builder $query) => $query->whereHas('tenants', function ($q) {
                                    if (!auth()->user()->hasRole(['super-admin', 'app-admin'])) {
                                        $q->where('tenants.id', tenant()->id);
                                    }
                                })
                            )
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'canceled' => 'Canceled',
                                'expired' => 'Expired',
                                'trial' => 'Trial',
                                'suspended' => 'Suspended',
                            ])
                            ->required()
                            ->default('active'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Billing Information')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->step(0.01),

                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->default('USD')
                            ->required()
                            ->maxLength(3),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->required()
                            ->default(now()->addMonth()),

                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->label('Canceled At')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(1000)
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Free' => 'gray',
                        'Basic' => 'warning',
                        'Premium' => 'success',
                        'Enterprise' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'canceled' => 'danger',
                        'expired' => 'warning',
                        'suspended' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('End Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'canceled' => 'Canceled',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ]),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->relationship('plan', 'name')
                    ->label('Plan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel Subscription')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Subscription $record) {
                        $record->update([
                            'status' => 'canceled',
                            'canceled_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $record): bool => $record->status === 'active'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                
                // App admins and super admins can see all subscriptions
                if ($user->hasRole(['super-admin', 'app-admin'])) {
                    return $query->with(['user', 'plan']);
                }
                
                // Tenant admins can only see subscriptions for their tenant users
                $tenantUserIds = $user->tenants()->first()?->users()->pluck('users.id') ?? [];
                return $query->whereIn('user_id', $tenantUserIds)->with(['user', 'plan']);
            });
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
            'index' => Pages\ListBilling::route('/'),
            'create' => Pages\CreateBilling::route('/create'),
            'view' => Pages\ViewBilling::route('/{record}'),
            'edit' => Pages\EditBilling::route('/{record}/edit'),
        ];
    }
}
