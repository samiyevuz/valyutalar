<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test';
    protected $description = 'Test database connection';

    public function handle(): int
    {
        $this->info('Testing database connection...');
        
        // Show current config
        $this->table(
            ['Setting', 'Value'],
            [
                ['Connection', config('database.default')],
                ['Host', config('database.connections.mysql.host')],
                ['Port', config('database.connections.mysql.port')],
                ['Database', config('database.connections.mysql.database')],
                ['Username', config('database.connections.mysql.username')],
                ['Password', config('database.connections.mysql.password') ? '***' : '(empty)'],
            ]
        );

        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful!');
            
            // Test query
            $result = DB::select('SELECT DATABASE() as db');
            $this->info('Current database: ' . ($result[0]->db ?? 'unknown'));
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile());
            $this->error('Line: ' . $e->getLine());
            
            return self::FAILURE;
        }
    }
}

