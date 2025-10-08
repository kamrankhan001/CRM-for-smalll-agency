<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log($subject, string $action, array $changes = null, string $description = null): void
    {
        $user = Auth::user();
        if (!$user) return;

        if ($subject instanceof Activity) return;

        Activity::create([
            'causer_id' => $user->id,
            'action' => $action,
            'subject_id' => $subject->id ?? null,
            'subject_type' => get_class($subject),
            'description' => $description ?? self::defaultDescription($subject, $action),
            'changes' => $changes ?? [],
        ]);
    }

    protected static function defaultDescription($subject, string $action): string
    {
        $model = strtolower(class_basename($subject));
        return match ($action) {
            'created' => "Created a new {$model}",
            'updated' => "Updated {$model} details",
            'deleted' => "Deleted {$model}",
            'assigned' => "Assigned {$model} to a user",
            'commented' => "Added a comment to {$model}",
            default => ucfirst($action) . " {$model}",
        };
    }
}
