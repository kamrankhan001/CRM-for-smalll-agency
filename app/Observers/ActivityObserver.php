<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class ActivityObserver
{
    public function created($model)
    {
        ActivityLogger::log($model, 'created');
    }

    public function updated($model)
    {
        $changes = [
            'from' => $model->getOriginal(),
            'to' => $model->getChanges(),
        ];

        ActivityLogger::log($model, 'updated', $changes);
    }

    public function deleted($model)
    {
        ActivityLogger::log($model, 'deleted');
    }

}
