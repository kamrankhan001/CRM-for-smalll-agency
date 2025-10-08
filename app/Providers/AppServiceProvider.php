<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Invoice;
use App\Observers\ActivityObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automatically log activity for these models
        Task::observe(ActivityObserver::class);
        Lead::observe(ActivityObserver::class);
        Client::observe(ActivityObserver::class);
        Note::observe(ActivityObserver::class);
        Project::observe(ActivityObserver::class);
        Document::observe(ActivityObserver::class);
        Invoice::observe(ActivityObserver::class);

        // Only log user actions if admin performs them
        User::observe(new class extends ActivityObserver
        {
            public function created($model)
            {
                if (Auth::check() && Auth::user()->role === 'admin') {
                    parent::created($model);
                }
            }

            public function updated($model)
            {
                if (Auth::check() && Auth::user()->role === 'admin') {
                    parent::updated($model);
                }
            }

            public function deleted($model)
            {
                if (Auth::check() && Auth::user()->role === 'admin') {
                    parent::deleted($model);
                }
            }
        });
    }
}
