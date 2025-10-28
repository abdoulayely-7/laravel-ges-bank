<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnblockExpiredAccounts;
use Illuminate\Console\Command;

class RunScheduledJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-scheduled-jobs {--job= : Spécifier un job spécifique (archive|unblock|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécuter les jobs planifiés pour la gestion des comptes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = $this->option('job') ?? 'all';

        $this->info('Démarrage des jobs planifiés...');

        switch ($job) {
            case 'archive':
                $this->runArchiveJob();
                break;
            case 'unblock':
                $this->runUnblockJob();
                break;
            case 'all':
            default:
                $this->runUnblockJob();
                $this->runArchiveJob();
                break;
        }

        $this->info('Jobs planifiés terminés.');
    }

    /**
     * Exécuter le job de déblocage automatique
     */
    private function runUnblockJob()
    {
        $this->info('Exécution du job de déblocage automatique...');
        UnblockExpiredAccounts::dispatch();
        $this->info('Job de déblocage envoyé à la queue.');
    }

    /**
     * Exécuter le job d'archivage automatique
     */
    private function runArchiveJob()
    {
        $this->info('Exécution du job d\'archivage automatique...');
        ArchiveExpiredBlockedAccounts::dispatch();
        $this->info('Job d\'archivage envoyé à la queue.');
    }
}
