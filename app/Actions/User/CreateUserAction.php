<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function execute(array $data): void
    {
        DB::transaction(function () use ($data) {
            // Password will be automatically hashed by the model cast
            User::create($data);
        });
    }
}