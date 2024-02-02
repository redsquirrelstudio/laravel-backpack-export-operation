<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Providers;

use Carbon\Carbon;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use RedSquirrelStudio\LaravelBackpackExportOperation\Events\ExportCompleteEvent;
use RedSquirrelStudio\LaravelBackpackExportOperation\Jobs\ProcessQueuedExportJob;

class ExportOperationProvider extends ServiceProvider
{

    /**
     * Perform post-registration booting of services.
     * @return void
     */
    public function boot(): void
    {
        Queue::after(function (JobProcessed $event) {
            try {
                if (isset($event->job->payload()['displayName']) && $event->job->payload()['displayName'] === ProcessQueuedExportJob::class) {
                    $export_log_id = unserialize($event->job->payload()['data']['command'])->getExportLogId();
                    $log_model = config('backpack.operations.export.export_log_model');
                    $log = $log_model::find($export_log_id);
                    $log->completed_at = Carbon::now();
                    $log->save();

                    ExportCompleteEvent::dispatch($log);
                }
            } catch (\Exception $e) {
            }
        });

        //Load Translations
        if (is_dir(resource_path('lang/vendor/backpack/import-operation'))) {
            $this->loadTranslationsFrom(resource_path('lang/vendor/backpack/export-operation'), 'export-operation');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'export-operation');
        }

        //Load Views
        if (is_dir(resource_path('views/vendor/backpack/export-operation'))) {
            $this->loadViewsFrom(resource_path('views/vendor/backpack/export-operation'), 'export-operation');
        }
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'export-operation');

        //Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'laravel-backpack-export-operation-migrations');

        //Publish config
        $this->publishes([
            __DIR__ . '/../../config/' => config_path('backpack/operations'),
        ], 'laravel-backpack-export-operation-config');

        //Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views/' => resource_path('views/vendor/backpack/export-operation'),
        ], 'laravel-backpack-export-operation-views');

        //Publish lang
        $this->publishes([
            __DIR__ . '/../../resources/lang/' => resource_path('lang/vendor/backpack/export-operation'),
        ], 'laravel-backpack-export-operation-translations');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing Views
        $this->publishes([
            __DIR__ . '/../../resources/views' => base_path('resources/views/vendor/backpack'),
        ], 'export-operation.views');

        // Publishing Translations
        $this->publishes([
            __DIR__ . '/../../resources/lang' => resource_path('lang/vendor/backpack/export-operation'),
        ], 'export-operation.lang');
    }

    /**
     * @return void
     */
    public function register(): void
    {

    }

}
