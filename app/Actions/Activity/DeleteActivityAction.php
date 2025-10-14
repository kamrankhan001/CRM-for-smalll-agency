<?php

namespace App\Actions\Activity;

use App\Models\Activity;
use Illuminate\Support\Facades\DB;

class DeleteActivityAction
{
    public function execute(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            $activity->delete();
        });
    }
}