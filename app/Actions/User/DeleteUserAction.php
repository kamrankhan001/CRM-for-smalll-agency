<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUserAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    }
}
