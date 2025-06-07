<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Product;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    
    protected bool $imageWarningConfirmed = false;
    
    public $imageWarningMessage = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    $product = $this->getRecord();
                    $product->update(['active' => 0]);
                    
                    $this->redirect(ProductResource::getUrl('index'));
                }),
        ];
    }
    
    protected function beforeSave(): void
    {
        // If warning was already confirmed, skip checking
        if ($this->imageWarningConfirmed) {
            return;
        }
        
        $data = $this->form->getState();
        $originalImage = $this->record->image;
        
        // Check if image is being removed (had image before, now empty)
        if (!empty($originalImage) && empty($data['image'])) {
            // Store warning message for display
            $this->imageWarningMessage = 'Afbeelding wordt verwijderd';
            
            // Show warning notification
            Notification::make()
                ->warning()
                ->title('⚠️ Afbeelding verwijderen')
                ->body('Je staat op het punt om de afbeelding van dit product te verwijderen. Afbeeldingen zijn belangrijk voor je website. Gebruik "Ja, Opslaan zonder afbeelding" om door te gaan.')
                ->persistent()
                ->send();
            
            // Halt the save process
            $this->halt();
        }
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if image is being removed
        if (!empty($this->imageWarningMessage)) {
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString(
                    view('components.warning-display', [
                        'icon' => 'photo',
                        'title' => 'Afbeelding verwijderen',
                        'message' => 'Je staat op het punt om de afbeelding van dit product te verwijderen. Afbeeldingen zijn belangrijk voor je website om producten effectief te tonen en om verkoop te verbeteren.'
                    ])->render()
                ))
                ->disabled()
                ->color('warning')
                ->extraAttributes(['style' => 'width: 100%; pointer-events: none; background: transparent; border: none; padding: 0;']);
        }
        
        $actions[] = $this->getSaveFormAction();
        
        // Add "Save Without Image" button when warning is shown
        $actions[] = Actions\Action::make('saveWithoutImage')
            ->label('Ja, Opslaan zonder afbeelding')
            ->color('danger')
            ->icon('heroicon-o-photo')
            ->modalHeading('Afbeelding verwijderen')
            ->modalDescription('Je staat op het punt om de afbeelding van dit product te verwijderen. Afbeeldingen zijn belangrijk voor je website om producten effectief te tonen en om verkoop te verbeteren.' . "\n\n" . 'Weet je zeker dat je dit product zonder afbeelding wilt opslaan?')
            ->modalSubmitActionLabel('Ja, Opslaan zonder afbeelding')
            ->modalCancelActionLabel('Annuleren')
            ->action(function () {
                // Set flag to bypass warning and save
                $this->imageWarningConfirmed = true;
                // Trigger save again
                $this->save();
            })
            ->visible(fn() => !empty($this->imageWarningMessage));
            
        $actions[] = $this->getCancelFormAction();
        
        return $actions;
    }
    
    protected function afterSave(): void
    {
        $data = $this->form->getState();
        
        // Show appropriate success message if image was removed
        if (empty($data['image'])) {
            Notification::make()
                ->success()
                ->title('✅ Product bijgewerkt')
                ->body('Het product is bijgewerkt zonder afbeelding. Overweeg om een afbeelding te uploaden om de klant betrokkenheid te verbeteren en het product effectief te tonen.')
                ->send();
        }
        
        // Reset flags for next time
        $this->imageWarningConfirmed = false;
        $this->imageWarningMessage = '';
    }
}
