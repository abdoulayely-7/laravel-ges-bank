<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveBlockedAccounts;
use App\Jobs\ProcessScheduledAccountBlocks;
use App\Jobs\UnarchiveExpiredBlockedAccounts;
use Illuminate\Console\Command;

class RunArchiveJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:run {--type= : Type of job to run (block|archive|unarchive|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run blocking and archive jobs for accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'all';

        $this->info("Running archive jobs (type: {$type})");

        switch ($type) {
            case 'block':
                $this->runBlockJob();
                break;
            case 'archive':
                $this->runArchiveJob();
                break;
            case 'unarchive':
                $this->runUnarchiveJob();
                break;
            case 'all':
            default:
                $this->runBlockJob();
                $this->runArchiveJob();
                $this->runUnarchiveJob();
                break;
        }

        $this->info('Archive jobs completed successfully!');
    }

    /**
     * Run the archive job
     */
    private function runArchiveJob()
    {
        $this->info('Dispatching ArchiveBlockedAccounts job...');
        ArchiveBlockedAccounts::dispatch();
        $this->info('ArchiveBlockedAccounts job dispatched successfully');
    }

    /**
     * Run the block job
     */
    private function runBlockJob()
    {
        $this->info('Dispatching ProcessScheduledAccountBlocks job...');
        ProcessScheduledAccountBlocks::dispatch();
        $this->info('ProcessScheduledAccountBlocks job dispatched successfully');
    }

    /**
     * Run the unarchive job
     */
    private function runUnarchiveJob()
    {
        $this->info('Dispatching UnarchiveExpiredBlockedAccounts job...');
        UnarchiveExpiredBlockedAccounts::dispatch();
        $this->info('UnarchiveExpiredBlockedAccounts job dispatched successfully');
    }
}
