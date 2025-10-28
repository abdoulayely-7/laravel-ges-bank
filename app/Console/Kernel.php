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
        // Exécuter les jobs planifiés tous les jours à minuit
        $schedule->command('app:run-scheduled-jobs')
            ->daily()
            ->runInBackground();

        // Alternative : exécuter directement les jobs
        // $schedule->job(new \App\Jobs\UnblockExpiredAccounts)->daily();
        // $schedule->job(new \App\Jobs\ArchiveExpiredBlockedAccounts)->daily();
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
