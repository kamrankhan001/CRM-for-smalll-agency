<?php

namespace App\Concerns;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;

trait HasMorphTypes
{
    /**
     * Map short type names to full model class names
     */
    public function mapMorphType(string $type): string
    {
        return match (strtolower($type)) {
            'lead' => Lead::class,
            'client' => Client::class,
            'project' => Project::class,
            default => $type,
        };
    }

    /**
     * Map full model class names to short type names
     */
    public function getShortMorphType(string $type): string
    {
        // Normalize the type for comparison
        $normalizedType = strtolower($type);

        return match (true) {
            $normalizedType === strtolower(Lead::class) || $normalizedType === 'lead' => 'lead',
            $normalizedType === strtolower(Client::class) || $normalizedType === 'client' => 'client',
            $normalizedType === strtolower(Project::class) || $normalizedType === 'project' => 'project',
            default => $type,
        };
    }

    /**
     * Get all available morph types for validation
     */
    public function getAvailableMorphTypes(): array
    {
        return ['lead', 'client', 'project'];
    }

    /**
     * Get morph type options for forms
     */
    public function getMorphTypeOptions(): array
    {
        return [
            ['value' => 'lead', 'label' => 'Lead'],
            ['value' => 'client', 'label' => 'Client'],
            ['value' => 'project', 'label' => 'Project'],
        ];
    }

    /**
     * Check if a given type is a valid morph type
     */
    public function isValidMorphType(string $type): bool
    {
        $normalizedType = strtolower($type);
        $validTypes = array_map('strtolower', [
            'lead', 'client', 'project',
            Lead::class, Client::class, Project::class,
        ]);

        return in_array($normalizedType, $validTypes);
    }

    /**
     * Normalize morph type to ensure consistent format
     */
    public function normalizeMorphType(string $type): string
    {
        return $this->mapMorphType($this->getShortMorphType($type));
    }
}
