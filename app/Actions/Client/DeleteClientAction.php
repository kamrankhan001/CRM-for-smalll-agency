<?php

namespace App\Actions\Client;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class DeleteClientAction
{
    public function execute(Client $client): void
    {
        DB::transaction(function () use ($client) {
            // Add any related data cleanup here if needed
            // Example: $client->projects()->delete();
            
            $client->delete();
        });
    }
}