<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function execute(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            // Remove password if empty (so it doesn't get set to empty string)
            if (empty($data['password'])) {
                unset($data['password']);
            }
            
            // Password will be automatically hashed by the model cast if provided
            $user->update($data);
        });
    }
}