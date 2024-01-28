<?php

use RedSquirrelStudio\LaravelBackpackExportOperation\Models\ExportLog;

/**
 * Configurations for ExportOperation.
 */

return [
    'export_log_model' => ExportLog::class,

    //Filesystem disk to store uploaded export files
    'disk' => env('FILESYSTEM_DISK', 'local'),

    //Path to store uploaded export files
    'path' => env('BACKPACK_EXPORT_FILE_PATH', 'exports'),

    //Queue to dispatch export jobs to
    'queue' => env('QUEUE_CONNECTION', 'sync'),
];
