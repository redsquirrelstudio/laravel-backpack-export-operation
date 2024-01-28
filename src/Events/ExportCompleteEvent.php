<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RedSquirrelStudio\LaravelBackpackExportOperation\Models\ExportLog;

class ExportCompleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ExportLog $export_log;

    /**
     * Create a new event instance.
     * @param ExportLog $export_log
     */
    public function __construct(ExportLog $export_log)
    {
        $this->export_log = $export_log;
        $this->export_log->completed_at = Carbon::now();
        $this->export_log->save();
    }
}
