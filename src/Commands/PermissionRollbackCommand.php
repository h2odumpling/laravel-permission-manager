<?php


namespace H2o\PermissionManager\Commands;


use Illuminate\Console\Command;
use H2o\PermissionManager\PermissionManager\PermissionManager;

class PermissionRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:rollback {date?} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'rollback permission.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $manager = new PermissionManager();

        if ($path = $this->option('path')) {
            $manager->setFilePath($path);
        }

        $date = $this->argument('date');
        if ($date === 'all') {
            $date = -1;
        } elseif ($date && strtotime($date) === false) {
            $this->info('Date format error.');
            return;
        }

        if ($count = $manager->rollback($date)) {
            $this->info("Rollback $count changes.");
        } else {
            $this->info('Nothing to rollback.');
        }
    }
}
