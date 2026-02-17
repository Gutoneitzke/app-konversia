<?php

namespace App\Providers;

use App\Jobs\CheckWhatsAppConnectionsStatus;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Verificar status das conexões WhatsApp a cada 5 minutos
            $schedule->job(CheckWhatsAppConnectionsStatus::class)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }
}