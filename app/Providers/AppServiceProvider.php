<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Policies\AttendancePolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate; // <-- Add this import

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
        Gate::policy(Attendance::class, AttendancePolicy::class);
    }
}
