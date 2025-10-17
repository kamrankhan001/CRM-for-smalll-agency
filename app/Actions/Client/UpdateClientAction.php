<?php

namespace App\Actions\Client;

use App\Models\Client;

class UpdateClientAction
{
    public function execute(Client $client, array $data): void
    {
        $client->update($data);
    }
}
