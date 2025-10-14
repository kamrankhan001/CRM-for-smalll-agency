<?php

namespace App\Concerns;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;

trait HasTaskableType
{
    public function mapTaskableType(string $type): string
    {
        return match ($type) {
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
            default => $type,
        };
    }

    public function getShortTaskableType(string $type): string
    {
        return match ($type) {
            Lead::class => 'lead',
            Client::class => 'client',
            Project::class => 'project',
            default => $type,
        };
    }
}