<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $roleIds = $this->form->getState()['roles'] ?? null;
        if ($roleIds !== null) {
            $ids = is_array($roleIds) ? $roleIds : [$roleIds];
            $this->record->syncRoles($ids);
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record->roles()->first()?->id;
        return $data;
    }
}
