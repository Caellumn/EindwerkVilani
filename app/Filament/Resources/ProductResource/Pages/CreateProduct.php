<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    protected bool $imageWarningConfirmed = false;
    
    public $imageWarningMessage = '';
    
    protected function beforeCreate(): void
    {
        // If warning was already confirmed, skip checking
        if ($this->imageWarningConfirmed) {
            return;
        }
        
        $data = $this->form->getState();
        
        // Check if no image is provided
        if (empty($data['image'])) {
            // Store warning message for display
            $this->imageWarningMessage = 'Geen afbeelding geselecteerd';
            
            // Show warning notification
            Notification::make()
                ->warning()
                ->title('⚠️ Geen afbeelding geselecteerd')
                ->body('Dit product heeft geen afbeelding. Afbeeldingen zijn belangrijk voor je website om producten effectief te tonen en om verkoop te verbeteren. Gebruik "Ja, Product maken zonder afbeelding" om door te gaan.')
                ->persistent()
                ->send();
            
            // Halt the creation process
            $this->halt();
        }
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if no image provided
        if (!empty($this->imageWarningMessage)) {
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString(
                    view('components.warning-display', [
                        'icon' => 'photo',
                        'title' => 'Geen afbeelding geselecteerd',
                        'message' => 'Een afbeelding is aanbevolen voor dit product. Je website heeft afbeeldingen nodig om producten effectief te tonen aan klanten en om verkoop te verbeteren.'
                    ])->render()
                ))
                ->disabled()
                ->color('warning')
                ->extraAttributes(['style' => 'width: 100%; pointer-events: none; background: transparent; border: none; padding: 0;']);
        }
        
        $actions[] = $this->getCreateFormAction();
        
        // Add "Create Without Image" button when warning is shown
        $actions[] = Actions\Action::make('createWithoutImage')
            ->label('Ja, Product maken zonder afbeelding')
            ->color('danger')
            ->icon('heroicon-o-photo')
            ->modalHeading('Geen afbeelding geselecteerd')
            ->modalDescription('Dit product heeft geen afbeelding. Afbeeldingen zijn belangrijk voor je website om producten effectief te tonen en om verkoop te verbeteren.' . "\n\n" . 'Weet je zeker dat je dit product zonder afbeelding wilt maken?')
            ->modalSubmitActionLabel('Ja, Product maken zonder afbeelding')
            ->modalCancelActionLabel('Annuleren')
            ->action(function () {
                // Set flag to bypass warning and create
                $this->imageWarningConfirmed = true;
                // Trigger creation again
                $this->create();
            })
            ->visible(fn() => !empty($this->imageWarningMessage));
            
        $actions[] = $this->getCancelFormAction();
        
        return $actions;
    }
    
    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        
        // Show appropriate success message
        if (empty($data['image'])) {
            Notification::make()
                ->success()
                ->title('✅ Product gemaakt')
                ->body('Het product is gemaakt zonder afbeelding! Afbeeldingen zijn belangrijk voor je website om producten effectief te tonen en om verkoop te verbeteren.')
                ->send();
        }
        
        // Reset flags for next time
        $this->imageWarningConfirmed = false;
        $this->imageWarningMessage = '';
    }
}
