<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->required()->maxLength(400),
                Forms\Components\TextInput::make('price')->required(),
                Forms\Components\TextInput::make('stock')->required(),
                Forms\Components\TextInput::make('image')
                    ->nullable()
                    ->label('Image URL')
                    ->helperText('The Cloudinary URL will appear here after upload')
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('uploadToCloudinary')
                            ->icon('heroicon-o-cloud-arrow-up')
                            ->label('Upload Image')
                            ->form([
                                Forms\Components\FileUpload::make('cloudinary_file')
                                    ->label('Select Image')
                                    ->image()
                                    ->required()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif']),
                            ])
                            ->action(function (array $data, Forms\Set $set) {
                                if (!isset($data['cloudinary_file']) || empty($data['cloudinary_file'])) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Upload failed')
                                        ->body('No file selected')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                try {
                                    // Get the uploaded file
                                    $uploadedFile = $data['cloudinary_file'];
                                    
                                    // In DDEV/Docker, we need to work with the temporary uploaded file directly
                                    if (is_string($uploadedFile)) {
                                        // If it's a string path, try to find the file
                                        $possiblePaths = [
                                            storage_path('app/public/' . $uploadedFile),
                                            storage_path('app/' . $uploadedFile),
                                            $uploadedFile,
                                            public_path('storage/' . $uploadedFile)
                                        ];
                                        
                                        $filePath = null;
                                        foreach ($possiblePaths as $path) {
                                            if (file_exists($path)) {
                                                $filePath = $path;
                                                break;
                                            }
                                        }
                                        
                                        if (!$filePath) {
                                            throw new \Exception('Could not locate uploaded file');
                                        }
                                    } else {
                                        throw new \Exception('Unexpected file format received');
                                    }
                                    
                                                                         // Upload to Cloudinary using direct approach
                                    $cloudinary = new \Cloudinary\Cloudinary([
                                        'cloud' => [
                                            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                                            'api_key' => env('CLOUDINARY_API_KEY'), 
                                            'api_secret' => env('CLOUDINARY_API_SECRET'),
                                        ]
                                    ]);
                                    
                                    $response = $cloudinary->uploadApi()->upload($filePath, [
                                        'folder' => 'products',
                                        'resource_type' => 'image'
                                    ]);
                                    
                                    // Set the Cloudinary URL
                                    $set('image', $response['secure_url']);
                                    
                                    // Clean up local file
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                    }
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('Upload successful')
                                        ->body('Image uploaded to Cloudinary successfully')
                                        ->success()
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Upload failed')
                                        ->body('Error: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ),
                Forms\Components\Section::make('Categories')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            //+ button
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('price')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('stock')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->color('success')
                    ->label('Categories')
                    ->bulleted(false)
                    ->searchable()
                    ->action(function ($record, $column, $state) {
                        // Get the category by name
                        $category = Category::where('name', $state)->first();
                        
                        if ($category) {
                            // Set the filter to this category by using closure reference to Livewire component
                            $livewire = $column->getTable()->getLivewire();
                            $livewire->tableFilters['categories']['values'] = [$category->id];
                        }
                    })
                    ->tooltip('Click to filter by this category'),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('description')->sortable()->searchable()->limit(50)->suffix('...'),

            ])
            ->filters([ 
                //
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Category'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status')
                    ->placeholder('All Products')
                    ->trueLabel('Active Products')
                    ->falseLabel('Deleted Products')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('active', 1),
                        false: fn (Builder $query): Builder => $query->where('active', 0),
                        blank: fn (Builder $query): Builder => $query
                    )
                    ->default(true)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['active' => 0]);
                    })
                    ->visible(fn (Product $record): bool => $record->active == 1), 
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-up')
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $record->update(['active' => 1]);
                    })
                    ->visible(fn (Product $record): bool => $record->active == 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['active' => 0]);
                            });
                        }),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
