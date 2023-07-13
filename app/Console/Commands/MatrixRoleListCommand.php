<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixRoleListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(Role::all()->pluck('name'));

        Log::info('Console role:list executed');
    }
}
