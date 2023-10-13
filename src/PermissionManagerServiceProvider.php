<?php

namespace H2o\PermissionManager;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PermissionManagerServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->publishes([
            __DIR__.'/../config/h2o-permission.php' => config_path('h2o-permission.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/update_permission_tables.php.stub' => $this->getMigrationFileName
            ($filesystem),
        ], 'migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\Show::class,
                Commands\PermissionMigrateCommand::class,
                Commands\PermissionRollbackCommand::class,
                Commands\PermissionMakeCommand::class,
            ]);
        }

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/h2o-permission.php',
            'h2o-permission'
        );
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_update_permission_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_update_permission_tables.php")
            ->first();
    }
}
