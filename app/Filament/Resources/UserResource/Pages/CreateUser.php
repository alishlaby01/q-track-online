<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $roleIds = $this->form->getState()['roles'] ?? null;
        if ($roleIds !== null) {
            $ids = is_array($roleIds) ? $roleIds : [$roleIds];
            $this->record->syncRoles($ids);
        }
    }
}
