<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Run update monitors daily
        $schedule->command('monitor:alumno-updates')->dailyAt('01:00');
        $schedule->command('monitor:tutor-updates')->dailyAt('01:30');
        
        // Clean up inactive containers weekly
        $schedule->command('docker:cleanup')->weekly()->sundays()->at('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
