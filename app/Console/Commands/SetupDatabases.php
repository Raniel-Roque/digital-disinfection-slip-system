<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:setup {--seed : Seed the database after migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create both databases (main and logs) and run migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up databases...');

        // Get the default connection name
        $defaultConnection = Config::get('database.default', 'mysql');
        
        // Create main database (using default connection)
        $this->createDatabase($defaultConnection);
        
        // Create logs database
        $this->createDatabase('logs');

        $this->info('');
        $this->info('Dropping existing tables...');
        
        // Drop tables from logs database
        $this->dropTablesFromConnection('logs');

        $this->info('');
        $this->info('Running migrations...');
        
        // Run migrations
        $this->call('migrate:fresh', [
            '--seed' => $this->option('seed'),
        ]);

        $this->info('');
        $this->info('✓ Database setup complete!');

        return self::SUCCESS;
    }

    /**
     * Create a database if it doesn't exist.
     */
    protected function createDatabase(string $connection): void
    {
        $connections = Config::get('database.connections', []);
        $config = $connections[$connection] ?? null;
        
        if (!$config) {
            $this->warn("Connection '{$connection}' not found in config. Skipping...");
            return;
        }

        $database = $config['database'];
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';

        $this->info("Creating database '{$database}' if it doesn't exist...");

        try {
            // Connect to MySQL without specifying a database
            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);

            $this->info("✓ Database '{$database}' is ready.");
        } catch (\PDOException $e) {
            $this->error("✗ Failed to create database '{$database}': " . $e->getMessage());
        }
    }

    /**
     * Drop all tables from a specific database connection.
     */
    protected function dropTablesFromConnection(string $connection): void
    {
        try {
            $connections = Config::get('database.connections', []);
            $config = $connections[$connection] ?? null;
            
            if (!$config) {
                return;
            }

            $databaseName = $config['database'];
            
            // Connect to the database connection
            DB::connection($connection)->getPdo();
            
            // Get all table names using information_schema (more reliable)
            $tables = DB::connection($connection)
                ->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$databaseName]);
            
            if (empty($tables)) {
                return;
            }

            // Disable foreign key checks
            DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0;');

            // Drop each table
            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME;
                DB::connection($connection)->statement("DROP TABLE IF EXISTS `{$tableName}`");
            }

            // Re-enable foreign key checks
            DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info("✓ Dropped all tables from '{$connection}' connection.");
        } catch (\Exception $e) {
            // Silently fail if connection doesn't exist or tables can't be dropped
            // This is fine for fresh setups
        }
    }
}