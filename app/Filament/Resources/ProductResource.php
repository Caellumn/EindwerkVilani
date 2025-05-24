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
use Illuminate\Support\HtmlString;

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
                    ->afterStateUpdated(function ($state) {
                        // This will trigger when the field value changes
                    }),

                Forms\Components\Placeholder::make('upload_section')
                    ->label('Upload Image to Cloudinary')
                    ->content(new HtmlString('
                        <div x-data="cloudinaryUpload()" 
                             class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center bg-gray-50 dark:bg-gray-800">
                            
                            <!-- File Input -->
                            <input type="file" 
                                   x-ref="fileInput" 
                                   accept="image/*" 
                                   class="block w-full text-sm text-gray-900 dark:text-gray-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300 dark:hover:file:bg-blue-800 mb-4">
                            
                            <!-- Upload Button -->
                            <button type="button" 
                                    @click="uploadToCloudinary()" 
                                    :disabled="uploading"
                                    :class="uploading ? \"opacity-50 cursor-not-allowed\" : \"hover:bg-blue-600 dark:hover:bg-blue-500 hover:shadow-lg transform hover:scale-105\""
                                    class="inline-flex items-center justify-center px-8 py-4 bg-blue-500 dark:bg-blue-600 !text-white font-semibold rounded-xl shadow-md transition-all duration-200 min-w-[240px] text-lg">
                                <span x-show="!uploading" class="flex items-center !text-white">
                                    <span class="text-2xl mr-3">📤</span>
                                    <span class="!text-white">Upload to Cloudinary</span>
                                </span>
                                <span x-show="uploading" class="flex items-center !text-white">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 !text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-lg !text-white">Uploading...</span>
                                </span>
                            </button>
                            
                            <!-- Status Messages -->
                            <div x-show="message" 
                                 x-text="message" 
                                 :class="messageType === \"success\" ? \"text-green-600 dark:text-green-400\" : messageType === \"error\" ? \"text-red-600 dark:text-red-400\" : \"text-blue-600 dark:text-blue-400\""
                                 class="mt-4 font-medium"></div>
                            
                            <!-- Image Preview -->
                            <div x-show="previewUrl" class="mt-4">
                                <img :src="previewUrl" 
                                     class="mx-auto max-w-xs max-h-48 rounded-lg shadow-md border border-gray-200 dark:border-gray-600">
                            </div>
                        </div>
                        
                        <script>
                        function cloudinaryUpload() {
                            return {
                                uploading: false,
                                message: "",
                                messageType: "",
                                previewUrl: "",
                                
                                uploadToCloudinary() {
                                    const file = this.$refs.fileInput.files[0];
                                    
                                    if (!file) {
                                        this.showMessage("⚠️ Please select a file first", "error");
                                        return;
                                    }
                                    
                                    // Validate file type
                                    if (!file.type.startsWith("image/")) {
                                        this.showMessage("❌ Please select an image file", "error");
                                        return;
                                    }
                                    
                                    // Validate file size (2MB)
                                    if (file.size > 2 * 1024 * 1024) {
                                        this.showMessage("❌ File too large. Max 2MB allowed", "error");
                                        return;
                                    }
                                    
                                    this.upload(file);
                                },
                                
                                async upload(file) {
                                    this.uploading = true;
                                    this.showMessage("⏳ Uploading to Cloudinary...", "info");
                                    
                                    try {
                                        const formData = new FormData();
                                        formData.append("image", file);
                                        formData.append("_token", document.querySelector("meta[name=csrf-token]").getAttribute("content"));
                                        
                                        const response = await fetch("/admin/upload-to-cloudinary", {
                                            method: "POST",
                                            body: formData
                                        });
                                        
                                        const data = await response.json();
                                        
                                        if (data.success) {
                                            this.handleSuccess(data.url);
                                        } else {
                                            this.showMessage("❌ " + (data.error || "Upload failed"), "error");
                                        }
                                    } catch (error) {
                                        this.showMessage("❌ Network error: " + error.message, "error");
                                    } finally {
                                        this.uploading = false;
                                    }
                                },
                                
                                handleSuccess(url) {
                                    // Update the image URL field
                                    const imageUrlField = document.querySelector("input[name=\"image\"]") || 
                                                         document.querySelector("input[id*=\"image\"]") ||
                                                         document.querySelector("input[wire\\\\:model*=\"image\"]");
                                    
                                    if (imageUrlField) {
                                        imageUrlField.value = url;
                                        imageUrlField.dispatchEvent(new Event("input", { bubbles: true }));
                                        imageUrlField.dispatchEvent(new Event("change", { bubbles: true }));
                                    }
                                    
                                    // Show success and preview
                                    this.showMessage("✅ Upload successful!", "success");
                                    this.previewUrl = url;
                                    
                                    // Clear file input
                                    this.$refs.fileInput.value = "";
                                },
                                
                                showMessage(text, type) {
                                    this.message = text;
                                    this.messageType = type;
                                    
                                    // Auto-clear non-error messages after 5 seconds
                                    if (type !== "error") {
                                        setTimeout(() => {
                                            if (this.messageType === type) {
                                                this.message = "";
                                                this.messageType = "";
                                            }
                                        }, 5000);
                                    }
                                }
                            }
                        }
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
