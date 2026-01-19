<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ExportDatabaseToSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-seeders {--all : Export all tables including transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database tables to seeder files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // List of master data tables to export by default
        $tables = [
            'users',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
            'companies',
            'coa',
            'bank',
            'bank_account',
            'cash_account',
            'products',
            'item_type',
            'uom',
            'supplier',
            'contact',
            'pph',
            'ppn',
            'currency',
            'service_charge',
            'service_charge_group',
            'settings',
        ];

        // If --all flag is provided, get all tables from database
        if ($this->option('all')) {
            $allTables = Schema::getTables(); // Laravel 11 method
            $tables = array_map(fn($t) => $t['name'], $allTables);

            // Exclude migrations and system tables
            $exclude = ['migrations', 'jobs', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'job_batches', 'password_reset_tokens'];
            $tables = array_diff($tables, $exclude);
        }

        $this->info('Starting export...');

        $generatedSeeders = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table '$table' not found. Skipping.");
                continue;
            }

            $data = DB::table($table)->get();

            if ($data->isEmpty()) {
                $this->line("Table '$table' is empty. Skipping.");
                continue;
            }

            $className = $this->createSeederFile($table, $data);
            $generatedSeeders[] = $className;
        }

        $this->info('Export completed!');
        $this->info('Generated Seeders:');
        foreach ($generatedSeeders as $seeder) {
            $this->line("- $seeder");
        }

        $this->info("\nDon't forget to add these to your DatabaseSeeder.php run() method!");
    }

    protected function createSeederFile($table, $data)
    {
        $className = Str::studly($table) . 'Seeder';
        $filePath = database_path("seeders/{$className}.php");

        $rows = [];
        foreach ($data as $row) {
            $rowArray = (array) $row;
            $rows[] = $this->formatRow($rowArray);
        }

        $dataString = implode(",\n            ", $rows);

        $content = "<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('{$table}')->truncate();

        DB::table('{$table}')->insert([
            {$dataString}
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
";

        File::put($filePath, $content);
        $this->info("Generated $className.php");

        return $className;
    }

    protected function formatRow($row)
    {
        $export = var_export($row, true);
        // Convert array() to []
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        // Remove integer keys
        $export = preg_replace('/^\s*\d+\s*=>\s*/m', '', $export);
        return $export;
    }
}
