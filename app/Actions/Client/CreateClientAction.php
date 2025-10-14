<?php

namespace App\Actions\Client;

use App\Models\Client;
use App\Models\User;

class CreateClientAction
{
    public function execute(array $data, User $currentUser): Client
    {
        $data['created_by'] = $currentUser->id;
        
        return Client::create($data);
    }
}