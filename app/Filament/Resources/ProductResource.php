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
                    ->readonly()
                    ->helperText('Upload an image using the button below'),

                Forms\Components\Placeholder::make('upload_placeholder')
                    ->label('Upload Image')
                    ->content(new HtmlString('
                        <div id="cloudinary-uploader">
                            <input type="file" id="image-upload" accept="image/*" style="margin-bottom: 10px;">
                            <button type="button" id="upload-btn" style="background: #3B82F6; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Upload to Cloudinary</button>
                            <div id="upload-status" style="margin-top: 10px;"></div>
                        </div>
                        
                        <script>
                            document.getElementById("upload-btn").addEventListener("click", function() {
                                const fileInput = document.getElementById("image-upload");
                                const statusDiv = document.getElementById("upload-status");
                                
                                if (!fileInput.files[0]) {
                                    statusDiv.innerHTML = "<span style=\"color: red;\">Please select a file first</span>";
                                    return;
                                }
                                
                                const formData = new FormData();
                                formData.append("image", fileInput.files[0]);
                                formData.append("_token", document.querySelector("meta[name=csrf-token]").content);
                                
                                statusDiv.innerHTML = "<span style=\"color: blue;\">Uploading...</span>";
                                
                                fetch("/admin/upload-to-cloudinary", {
                                    method: "POST",
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        const imageField = document.querySelector("input[wire\\:model=\"mountedFormComponentActionsData.0.image\"]") || 
                                                         document.querySelector("input[name=\"image\"]");
                                        if (imageField) {
                                            imageField.value = data.url;
                                            imageField.dispatchEvent(new Event("input"));
                                        }
                                        statusDiv.innerHTML = "<span style=\"color: green;\">✓ Upload successful!</span>";
                                        fileInput.value = "";
                                    } else {
                                        statusDiv.innerHTML = "<span style=\"color: red;\">Error: " + data.error + "</span>";
                                    }
                                })
                                .catch(error => {
                                    statusDiv.innerHTML = "<span style=\"color: red;\">Upload failed: " + error.message + "</span>";
                                });
                            });
                        </script>
                    '))
                    ->hiddenOn('view'),
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
