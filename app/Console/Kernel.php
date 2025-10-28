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
        // Traiter les blocages planifiés toutes les minutes
        $schedule->job(new \App\Jobs\ProcessScheduledAccountBlocks)->everyMinute();

        // Archiver les comptes bloqués tous les jours à 2h du matin
        $schedule->job(new \App\Jobs\ArchiveBlockedAccounts)->dailyAt('02:00');

        // Désarchiver les comptes expirés toutes les heures
        $schedule->job(new \App\Jobs\UnarchiveExpiredBlockedAccounts)->hourly();
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
