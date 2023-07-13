<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixPermListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'perm:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all permission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(Permission::all()->pluck('name'));

        Log::info('Console perm:list executed');
    }
}
