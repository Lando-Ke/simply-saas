<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tenants';
    
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tenant Information')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Tenant ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->default(fn () => Str::uuid())
                            ->disabled(fn (string $context): bool => $context === 'edit'),
                        
                        Forms\Components\TextInput::make('data.name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('data.email')
                            ->label('Organization Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Subscription')
                    ->schema([
                        Forms\Components\Select::make('data.status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                                'trial' => 'Trial',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Select::make('data.subscription_plan')
                            ->label('Subscription Plan')
                            ->options([
                                'free' => 'Free',
                                'basic' => 'Basic',
                                'premium' => 'Premium',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('free')
                            ->required(),

                        Forms\Components\DateTimePicker::make('data.trial_ends_at')
                            ->label('Trial Ends At')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('data.subscription_ends_at')
                            ->label('Subscription Ends At')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Domain Configuration')
                    ->schema([
                        Forms\Components\Repeater::make('domains')
                            ->relationship('domains')
                            ->schema([
                                Forms\Components\TextInput::make('domain')
                                    ->label('Domain')
                                    ->required()
                                    ->unique(table: 'domains', ignoreRecord: true)
                                    ->suffixIcon('heroicon-o-globe-alt'),
                            ])
                            ->addActionLabel('Add Domain')
                            ->defaultItems(0)
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Branding Settings')
                    ->schema([
                        Forms\Components\TextInput::make('data.settings.branding.primary_color')
                            ->label('Primary Color')
                            ->placeholder('#4F46E5'),

                        Forms\Components\TextInput::make('data.settings.branding.secondary_color')
                            ->label('Secondary Color')
                            ->placeholder('#6B7280'),

                        Forms\Components\FileUpload::make('data.settings.branding.logo')
                            ->label('Logo')
                            ->image()
                            ->directory('tenant-logos')
                            ->visibility('public'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Tenant ID')
                    ->searchable()
                    ->copyable()
                    ->limit(8),

                Tables\Columns\TextColumn::make('data.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data.email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\BadgeColumn::make('data.status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'trial',
                        'danger' => 'suspended',
                        'secondary' => 'inactive',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('data.subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->colors([
                        'secondary' => 'free',
                        'primary' => 'basic',
                        'success' => 'premium',
                        'warning' => 'enterprise',
                    ]),

                Tables\Columns\TextColumn::make('domains_count')
                    ->label('Domains')
                    ->counts('domains')
                    ->badge(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('data.status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                        'trial' => 'Trial',
                    ]),

                Tables\Filters\SelectFilter::make('data.subscription_plan')
                    ->label('Plan')
                    ->options([
                        'free' => 'Free',
                        'basic' => 'Basic',
                        'premium' => 'Premium',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('switch_to_tenant')
                        ->label('Switch to Tenant')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('primary')
                        ->action(function (Tenant $record) {
                            // Initialize the tenant context
                            tenancy()->initialize($record);
                            
                            // Get the primary domain
                            $domain = $record->getPrimaryDomain();
                            
                            if ($domain) {
                                // Redirect to the tenant's domain
                                return redirect()->away("http://{$domain}/admin");
                            }
                            
                            // If no domain, show error
                            \Filament\Notifications\Notification::make()
                                ->title('No Domain Found')
                                ->body('This tenant has no configured domains.')
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Switch to Tenant')
                        ->modalDescription('This will switch you to the tenant\'s admin panel. You may need to log in again.')
                        ->modalSubmitActionLabel('Switch'),

                    Tables\Actions\Action::make('impersonate')
                        ->label('Manage Tenant')
                        ->icon('heroicon-o-user-circle')
                        ->color('warning')
                        ->visible(fn (): bool => self::isSuperAdmin())
                        ->action(function (Tenant $record) {
                            session(['impersonating_tenant' => $record->id]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Now Managing Tenant')
                                ->body("You are now managing {$record->getName()}. Switch back when done.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (): bool => self::isSuperAdmin())
                        ->requiresConfirmation()
                        ->modalHeading('Delete Tenant')
                        ->modalDescription('This will permanently delete the tenant and all associated data. This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete Tenant'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => self::isSuperAdmin()),
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

    public static function canAccess(): bool
    {
        return self::isAppOrSuperAdmin();
    }

    private static function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user instanceof \App\Models\User && $user->hasRole('super-admin');
    }

    private static function isAppOrSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user instanceof \App\Models\User && $user->hasAnyRole(['super-admin', 'app-admin']);
    }
}