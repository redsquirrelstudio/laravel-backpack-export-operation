<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use RedSquirrelStudio\LaravelBackpackExportOperation\Exports\CrudExport;

class ProcessQueuedExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $export_log_id;
    protected string $file_path;
    protected string $disk;

    /**
     * Create a new job instance.
     */
    public function __construct(int $export_log_id, string $file_path, string $disk)
    {
        $this->export_log_id = $export_log_id;
        $this->file_path = $file_path;
        $this->disk = $disk;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Excel::store(new CrudExport($this->export_log_id), $this->file_path, $this->disk);
    }

    /**
     * @return int
     */
    public function getExportLogId(): int
    {
        return $this->export_log_id;
    }
}
