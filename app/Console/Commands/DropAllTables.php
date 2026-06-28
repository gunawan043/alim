<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DropAllTables extends Command
{
    protected $signature = 'db:drop-all';
    protected $description = 'Drop ALL tables including migrations table';

    public function handle()
    {
        $dbName = config('database.connections.mysql.database');
        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Drop and recreate the database
        $pdo = new \PDO(
            "mysql:host=$dbHost",
            $dbUser,
            $dbPass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = null;

        $this->info("Dropped and recreated database `$dbName`.");

        return 0;
    }
}
