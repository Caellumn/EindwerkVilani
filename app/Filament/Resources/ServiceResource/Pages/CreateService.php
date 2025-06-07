<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
    
    public $bypassCategoryWarning = false;
    public $showCategoryWarning = false;
    
    protected function beforeCreate(): void
    {
        // If warning was already bypassed, skip checking
        if ($this->bypassCategoryWarning) {
            return;
        }
        
        $data = $this->form->getState();
        
        // Check if no categories are selected
        if (empty($data['categories']) || count($data['categories']) === 0) {
            // Set flag to show warning in form actions
            $this->showCategoryWarning = true;
            
            // Show warning notification
            Notification::make()
                ->warning()
                ->title('⚠️ Geen categorieën geselecteerd')
                ->body('Deze dienst heeft geen categorieën toegewezen. Categorieën helpen klanten de dienst beter te vinden en filteren. Gebruik "Ja, Maak zonder categorieën" om door te gaan.')
                ->persistent()
                ->send();
            
            // Halt the creation process
            $this->halt();
        }
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if no categories selected
        if ($this->showCategoryWarning) {
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString(
                    view('components.warning-display', [
                        'icon' => 'exclamation-triangle',
                        'title' => 'Geen categorieën geselecteerd',
                        'message' => 'Deze dienst heeft geen categorieën toegewezen. Categorieën helpen klanten de dienst beter te vinden en filteren.'
                    ])->render()
                ))
                ->disabled()
                ->color('warning')
                ->extraAttributes(['style' => 'width: 100%; pointer-events: none; background: transparent; border: none; padding: 0;']);
        }
        
        $actions[] = $this->getCreateFormAction();
        
        // Add "Create Without Categories" button when warning is shown
        $actions[] = Actions\Action::make('createWithoutCategories')
            ->label('Ja, Maak zonder categorieën')
            ->color('danger')
            ->icon('heroicon-o-exclamation-triangle')
            ->action(function () {
                // Set flag to bypass warning and create
                $this->bypassCategoryWarning = true;
                $this->showCategoryWarning = false;
                $this->create();
            })
            ->visible(fn() => $this->showCategoryWarning);
            
        $actions[] = $this->getCancelFormAction();
        
        return $actions;
    }
    
    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        
        // Show appropriate success message
        if (empty($data['categories'])) {
            Notification::make()
                ->success()
                ->title('✅ Service gemaakt')
                ->body('De dienst is gemaakt zonder categorieën. Je kunt later categorieën toevoegen door de dienst te bewerken.')
                ->send();
        }
        
        // Reset flags for next time
        $this->bypassCategoryWarning = false;
        $this->showCategoryWarning = false;
    }
}
