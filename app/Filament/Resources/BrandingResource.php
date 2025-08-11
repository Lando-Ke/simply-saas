<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandingResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BrandingResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $tenantOwnershipRelationshipName = null;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?string $modelLabel = 'Branding';

    protected static ?string $pluralModelLabel = 'Branding';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

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
        
        // Tenant admins can only view their own tenant's branding
        return $user->canAccessTenant($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brand Identity')
                    ->schema([
                        Forms\Components\TextInput::make('data.name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('data.settings.branding.logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('tenant-logos')
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/jpg', 'image/jpeg', 'image/svg+xml'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Color Scheme')
                    ->schema([
                        Forms\Components\ColorPicker::make('data.settings.branding.primary_color')
                            ->label('Primary Color')
                            ->default('#4F46E5')
                            ->required(),
                        
                        Forms\Components\ColorPicker::make('data.settings.branding.secondary_color')
                            ->label('Secondary Color')
                            ->default('#6B7280')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\Textarea::make('data.settings.branding.tagline')
                            ->label('Tagline')
                            ->maxLength(500)
                            ->rows(2),
                        
                        Forms\Components\TextInput::make('data.settings.branding.website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('data.settings.branding.logo')
                    ->label('Logo')
                    ->disk('public')
                    ->size(50)
                    ->circular(),
                
                Tables\Columns\TextColumn::make('data.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\ColorColumn::make('data.settings.branding.primary_color')
                    ->label('Primary Color')
                    ->copyable(),
                
                Tables\Columns\ColorColumn::make('data.settings.branding.secondary_color')
                    ->label('Secondary Color')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk delete for branding
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                
                // App admins and super admins can see all tenants
                if ($user->hasRole(['super-admin', 'app-admin'])) {
                    return $query;
                }
                
                // Tenant admins can only see their own tenant
                $tenantIds = $user->tenants()->pluck('tenants.id');
                return $query->whereIn('id', $tenantIds);
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
            'index' => Pages\ListBranding::route('/'),
            'edit' => Pages\EditBranding::route('/{record}/edit'),
        ];
    }
}
