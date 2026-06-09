<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupDb extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:setup';
    protected $description = 'Creates the nexusdb database, runs migrations, and seeds mock data.';

    public function run(array $params)
    {
        CLI::write('Starting nexusdb setup...', 'yellow');

        // Connect natively to create the database if not exists (prevents connection errors in CI4)
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $port = 3306;

        CLI::write("Connecting to MySQL at {$host}...", 'cyan');

        $mysqli = @new \mysqli($host, $user, $pass, '', $port);

        if ($mysqli->connect_error) {
            CLI::error("Connection failed: " . $mysqli->connect_error);
            CLI::write("Please verify that your XAMPP MySQL server is running.", 'red');
            return;
        }

        CLI::write("Creating database 'nexusdb' if it doesn't exist...", 'cyan');
        if ($mysqli->query("CREATE DATABASE IF NOT EXISTS nexusdb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            CLI::write("Database 'nexusdb' verified/created successfully.", 'green');
        } else {
            CLI::error("Error creating database: " . $mysqli->error);
            $mysqli->close();
            return;
        }
        $mysqli->close();

        // Now run CodeIgniter migrations
        CLI::write("Running database migrations...", 'cyan');
        try {
            command('migrate');
            CLI::write("Migrations completed successfully.", 'green');
        } catch (\Throwable $e) {
            CLI::error("Migration failed: " . $e->getMessage());
            return;
        }

        // Run seeders
        CLI::write("Seeding sample data...", 'cyan');
        try {
            command('db:seed MainSeeder');
            CLI::write("Seeding completed successfully.", 'green');
        } catch (\Throwable $e) {
            CLI::error("Seeding failed: " . $e->getMessage());
            return;
        }

        CLI::write("Database 'nexusdb' setup completed successfully!", 'black', 'green');
    }
}
