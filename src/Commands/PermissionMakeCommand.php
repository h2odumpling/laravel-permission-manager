<?php

namespace H2o\PermissionManager\Commands;


use Illuminate\Console\Command;
use H2o\PermissionManager\Manager\PermissionMaker;

class PermissionMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:make {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make permission.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $maker = new PermissionMaker();
        $type = $this->argument('type') ?: 'admin';
        if ($count = $maker->setType($type)->sync()) {
            $this->info("Save $count changes.");
        } else {
            $this->info('Nothing to save.');
        }
    }
}
