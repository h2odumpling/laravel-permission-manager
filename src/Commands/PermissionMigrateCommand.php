<?php

namespace H2o\PermissionManager\Commands;


use Illuminate\Console\Command;
use H2o\PermissionManager\Manager\PermissionManager;

class PermissionMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:migrate {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate permission.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $manager = new PermissionManager();
        if ($count = $manager->sync()) {
            $this->info("Save $count changes.");
        } else {
            $this->info('Nothing to save.');
        }
    }
}
