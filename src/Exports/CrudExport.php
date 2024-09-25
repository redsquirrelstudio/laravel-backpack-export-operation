<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CrudExport implements FromView, ShouldAutoSize
{
    protected $export_log;
    protected $crud;

    /**
     * @param int $export_log_id
     */
    public function __construct(int $export_log_id)
    {
        $log_model = config('backpack.operations.export.export_log_model');
        $this->export_log = $log_model::find($export_log_id);
        $this->crud = app('crud');
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($this->export_log->id);

        $this->crud->setModel($log->model);

        // No filters
        if (collect($log->config['query'])->count() === 0) {
            $entries = $log->model::all();;
        } else {
            $this->applyFilters($log->config['query']);
            $entries = $this->crud->getEntries();
        }

        return view('export-operation::exports.crud-export', [
            'config' => $log->config,
            'entries' => $entries,
            'crud' => $this->crud
        ]);
    }

    /**
     * @return Model
     */
    protected function getExportLog(): Model
    {
        return $this->export_log;
    }

    private function applyFilters($requestQueryFromLog)
    {
        $request = $this->crud->getRequest();
        collect($requestQueryFromLog)->each(function ($value, $key) use ($request) {
            $request->query->set($key, $value);
        });

        $this->crud->applyDatatableOrder();
        $this->crud->filters()->each(function ($filter) {
            $filter->apply();
        });

        $search = $request->input('search');
        if ($search && $search['value'] ?? false) {
            $this->crud->applySearchTerm($search['value']);
        }
    }
}
