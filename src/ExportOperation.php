<?php

namespace RedSquirrelStudio\LaravelBackpackExportOperation;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Excel as BaseExcel;

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
     * @return View
     */
    public function setupExport(): View
    {
        $this->crud->hasAccessOrFail('import');

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
                $config[] = collect($column)->filter(
                    fn($value, $key) => in_array($key, [
                            'name', 'label', 'type', 'options', 'separator', 'multiple',
                        ]
                    ))->toArray();
            }
        }

        $log_model = config('backpack.operations.export.export_log_model');
        $log = $log_model::create([
            'user_id' => backpack_user()->id,
            'file_type' => $request->get('file_type'),
            'disk' => config('backpack.operations.export.disk'),
            'model' => $this->crud->model,
            'config' => $config,
        ]);

        return redirect($this->crud->route.'/'.$log->id.'/process');
    }

}
