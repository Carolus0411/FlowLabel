<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database setiap hari Kamis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');
        $this->call('backup:run');
        $this->info('Database backup completed.');
    }
}
