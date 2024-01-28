<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use RedSquirrelStudio\LaravelBackpackExportOperation\Events\ExportCompleteEvent;

class CrudExport implements FromView, ShouldAutoSize, WithEvents
{
    protected $export_log;

    /**
     * @param int $export_log_id
     */
    public function __construct(int $export_log_id)
    {
        $log_model = config('backpack.operations.export.export_log_model');
        $this->export_log =  $log_model::find($export_log_id);
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($this->export_log->id);

        $entries = $log->model::all();
        return view('export-operation::exports.crud-export', [
            'config' => $log->config,
            'entries' => $entries,
        ]);
    }

    /**
     * @return Model
     */
    protected function getExportLog(): Model
    {
        return $this->export_log;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event){
                $exporter = $event->getConcernable();
                $log = $exporter->getExportLog();
                $log->completed_at = Carbon::now();
                $log->save();

                ExportCompleteEvent::dispatch($log);
            }
        ];
    }
}
