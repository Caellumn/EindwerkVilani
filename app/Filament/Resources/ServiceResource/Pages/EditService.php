<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;
    
    public $bypassCategoryWarning = false;
    public $showCategoryWarning = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function ($record) {
                    // Set active to 0 instead of deleting
                    $record->update(['active' => 0]);
                    
                    // Redirect to the listing page
                    return redirect()->to(ServiceResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Deactivate Service')
                ->modalDescription('Are you sure you want to deactivate this service? This will set the active status to inactive.')
                ->modalSubmitActionLabel('Deactivate')
                ->successNotificationTitle('Service deactivated successfully'),
        ];
    }
    
    protected function beforeSave(): void
    {
        // If warning was already bypassed, skip checking
        if ($this->bypassCategoryWarning) {
            return;
        }
        
        $data = $this->form->getState();
        $originalCategories = $this->record->categories()->pluck('id')->toArray();
        
        // Check if categories are being removed (had categories before, now empty)
        if (!empty($originalCategories) && (empty($data['categories']) || count($data['categories']) === 0)) {
            // Set flag to show warning in form actions
            $this->showCategoryWarning = true;
            
            // Show warning notification
            Notification::make()
                ->warning()
                ->title('⚠️ Removing All Categories')
                ->body('You are about to remove all categories from this service. This may make it harder for customers to find this service. Use "Yes, Save Without Categories" to proceed.')
                ->persistent()
                ->send();
            
            // Halt the save process
            $this->halt();
        }
    }
    
    protected function getFormActions(): array
    {
        $actions = [];
        
        // Add warning message if categories are being removed
        if ($this->showCategoryWarning) {
            $actions[] = Actions\Action::make('warningDisplay')
                ->label(new \Illuminate\Support\HtmlString(
                    view('components.warning-display', [
                        'icon' => 'exclamation-triangle',
                        'title' => 'Alle categorieën verwijderen',
                        'message' => 'Je staat op het punt om alle categorieën van deze dienst te verwijderen. Dit kan het vinden van deze dienst moeilijker maken voor klanten.'
                    ])->render()
                ))
                ->disabled()
                ->color('warning')
                ->extraAttributes(['style' => 'width: 100%; pointer-events: none; background: transparent; border: none; padding: 0;']);
        }
        
        $actions[] = $this->getSaveFormAction();
        
        // Add "Save Without Categories" button when warning is shown
        $actions[] = Actions\Action::make('saveWithoutCategories')
            ->label('Ja, Opslaan zonder categorieën')
            ->color('danger')
            ->icon('heroicon-o-exclamation-triangle')
            ->action(function () {
                // Set flag to bypass warning and save
                $this->bypassCategoryWarning = true;
                $this->showCategoryWarning = false;
                $this->save();
            })
            ->visible(fn() => $this->showCategoryWarning);
            
        $actions[] = $this->getCancelFormAction();
        
        return $actions;
    }
    
    protected function afterSave(): void
    {
        $data = $this->form->getState();
        
        // Show appropriate success message if categories were removed
        if (empty($data['categories'])) {
            Notification::make()
                ->success()
                ->title('✅ Service bijgewerkt')
                ->body('De dienst is bijgewerkt zonder categorieën. Je kunt later categorieën toevoegen door de dienst te bewerken.')
                ->send();
        }
        
        // Reset flags for next time
        $this->bypassCategoryWarning = false;
        $this->showCategoryWarning = false;
    }
}
