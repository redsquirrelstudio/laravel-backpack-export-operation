<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as BaseExcel;
use Maatwebsite\Excel\Facades\Excel;
use RedSquirrelStudio\LaravelBackpackExportOperation\Exports\CrudExport;
use RedSquirrelStudio\LaravelBackpackExportOperation\Jobs\ProcessQueuedExportJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     * @return void
     */
    protected function setupExportRoutes(string $segment, string $routeName, string $controller): void
    {
        Route::get($segment . '/export', [
            'as' => $routeName . '.export',
            'uses' => $controller . '@setupExport',
            'operation' => 'export',
        ]);

        Route::post($segment . '/export', [
            'as' => $routeName . '.export',
            'uses' => $controller . '@createExport',
            'operation' => 'export',
        ]);

        Route::get($segment . '/export/{id}/process', [
            'as' => $routeName . '.process',
            'uses' => $controller . '@processExport',
            'operation' => 'export',
        ]);

        Route::post($segment . '/export/{id}/start', [
            'as' => $routeName . '.start',
            'uses' => $controller . '@startExport',
            'operation' => 'export',
        ]);

        Route::get($segment . '/export/{id}/check', [
            'as' => $routeName . '.check',
            'uses' => $controller . '@checkExport',
            'operation' => 'export',
        ]);

        Route::get($segment . '/export/{id}/download', [
            'as' => $routeName . '.download',
            'uses' => $controller . '@downloadExport',
            'operation' => 'export',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     * @return void
     */
    protected function setupExportDefaults(): void
    {
        CRUD::allowAccess('export');
        CRUD::enableGroupedErrors();
        CRUD::operation('export', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });
        CRUD::operation('list', function () {
            CRUD::addButton('top', 'export', 'view', 'export-operation::buttons.export_button');
        });
    }

    /**
     * Disable the user configuration step, always export as per the dev config
     * @return void
     */
    public function disableUserConfiguration(): void
    {
        CRUD::setOperationSetting('disableUserConfiguration', true);
    }


    /**
     * Queue exports to be handled in the background
     * @return void
     */
    public function queueExport(): void
    {
        CRUD::setOperationSetting('queueExport', true);
    }

    /**
     * Set the message that should be displayed to the user after they
     * queue a new export
     * @param string $message
     * @return void
     */
    public function setQueueMessage(string $message): void
    {
        CRUD::setOperationSetting('exportQueueMessage', $message);
    }

    /**
     * @return View
     */
    public function setupExport(): View
    {
        $this->crud->hasAccessOrFail('export');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? __('export-operation::export.export', [
            'entity' => $this->crud->entity_name_plural
        ]);

        $file_formats = [
            BaseExcel::CSV => __('export-operation::export.csv'),
            BaseExcel::XLS => __('export-operation::export.xls'),
            BaseExcel::XLSX => __('export-operation::export.xlsx'),
            BaseExcel::ODS => __('export-operation::export.ods'),
        ];

        if (class_exists('\Dompdf\Dompdf')) {
            $file_formats[BaseExcel::DOMPDF] = __('export-operation::export.pdf');
        }


        $this->data['columns'] = $this->crud->columns();
        $this->data['config_disabled'] = $this->crud->getOperationSetting('disableUserConfiguration', 'export') ?? false;

        CRUD::addField([
            'name' => 'file_type',
            'label' => __('export-operation::export.file_type'),
            'type' => 'select_from_array',
            'options' => $file_formats,
            'allows_null' => false,
            'default' => BaseExcel::CSV
        ]);

        return view('export-operation::configure-export', $this->data);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function createExport(Request $request): RedirectResponse
    {
        $this->crud->hasAccessOrFail('export');

        $validation_rules = [
            'file_type' => 'required:in:csv,xls,xlsx,ods,pdf'
        ];
        foreach ($this->crud->columns() as $column) {
            $validation_rules['include_' . $column['name']] = 'required|in:0,1';
        }

        $request->validate($validation_rules);

        $config = [];
        foreach ($this->crud->columns() as $column) {
            if ((int)$request->get('include_' . $column['name']) === 1) {
                $config[] = $column;
            }
        }

        if (count($config) === 0){
            return redirect($this->crud->route.'/export')->withErrors([
                'export' => __('export-operation::export.please_include_at_least_one'),
            ]);
        }

        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::create([
            'user_id' => backpack_user()->id,
            'file_type' => $request->get('file_type'),
            'disk' => config('backpack.operations.export.disk'),
            'model' => get_class($this->crud->model),
            'config' => $config,
        ]);

        $export_should_queue = $this->crud->getOperationSetting('queueExport', 'export') ?? false;
        if ($export_should_queue){
            $file_name = strtolower(__('export-operation::export.export')) . '_' .
                str_replace(' ', '_', strtolower($this->crud->entity_name_plural))  . '_' .
                Carbon::now()->format('d-m-y-H-i-s') . '_' .
                Str::uuid() . '.' . strtolower($log->file_type === 'Dompdf' ? 'pdf' : $log->file_type);
            $file_path = config('backpack.operations.export.path') . '/' . $file_name;

            $log->file_path = $file_path;
            $log->started_at = Carbon::now();
            $log->save();

            ProcessQueuedExportJob::dispatch($log->id, $file_path, $log->disk);
            $message = $this->crud->getOperationSetting('exportQueueMessage', 'export') ?? __('export-operation::export.your_export_will_be_processed');
            \Alert::add('success', $message)->flash();
            return redirect($this->crud->route);
        }

        return redirect($this->crud->route . '/export/' . $log->id . '/process');
    }


    /**
     * @param int $id
     * @return View
     */
    public function processExport(int $id): View
    {
        $this->crud->hasAccessOrFail('export');
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($id);

        if (!$log) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? __('export-operation::export.export', [
            'entity' => $this->crud->entity_name_plural
        ]);
        $this->data['entry'] = $log;

        return view('export-operation::process-export', $this->data);
    }

    /**
     * @param int $id
     * @return Response
     */
    public function startExport(int $id): Response
    {
        $this->crud->hasAccessOrFail('export');
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($id);

        if (!$log) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (is_null($log->started_at)) {
            $disk = $log->disk;
            $file_name = strtolower(__('export-operation::export.export')) . '_' .
                str_replace(' ', '_', strtolower($this->crud->entity_name_plural)) . '_' .
                Carbon::now()->format('d-m-y-H-i-s') . '_' .
                Str::uuid() . '.' . strtolower($log->file_type === 'Dompdf' ? 'pdf' : $log->file_type);
            $file_path = config('backpack.operations.export.path') . '/' . $file_name;
            $log->file_path = $file_path;
            $log->started_at = Carbon::now();
            $log->save();

            Excel::store(new CrudExport($log->id), $file_path, $disk);
        }

        return response([
            'success' => true,
        ]);
    }

    /**
     * @param int $id
     * @return Response
     */
    public function checkExport(int $id): Response
    {
        $this->crud->hasAccessOrFail('export');
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($id);

        if (!$log) {
            abort(Response::HTTP_NOT_FOUND);
        }
        $exists = $log->file_path && Storage::disk($log->disk)->exists($log->file_path);
        return response([
            'complete' => $exists,
            'file_name' => $log->file_path
        ]);
    }

    /**
     * @param int $id
     * @return StreamedResponse
     */
    public function downloadExport(int $id): StreamedResponse
    {
        $this->crud->hasAccessOrFail('export');
        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::find($id);

        if (!$log) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk($log->disk)->download($log->file_path);
    }
}
