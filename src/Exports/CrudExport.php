<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CrudExport implements FromView, ShouldAutoSize
{
    protected int $log_id;

    public function __construct(int $log_id)
    {
        $this->log_id = $log_id;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($this->log_id);

        $entries = $log->model::all();
        return view('export-operation::exports.crud-export', [
            'config' => $log->config,
            'entries' => $entries,
        ]);
    }
}
