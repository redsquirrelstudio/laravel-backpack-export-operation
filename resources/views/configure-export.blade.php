@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('export-operation::export.export') => false,
    ];

    // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">
                {!! $crud->getHeading() ?? $crud->entity_name_plural !!}
            </span>
            <small>
                {!! $crud->getSubheading() ?? trans('export-operation::export.export_entity', [
                    'entity' => $crud->entity_name_plural
                ]) !!}
            </small>

            @if ($crud->hasAccess('list'))
                <small>
                    <a href="{{ url($crud->route) }}" class="d-print-none font-sm">
                        <i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>
                            {{ $crud->entity_name_plural }}
                        </span>
                    </a>
                </small>
            @endif
        </h2>
    </section>
@endsection

@section('content')

    <div class="row">
        <div class="col-md-8">
            {{-- Default box --}}

            @include('crud::inc.grouped_errors')

            <form method="post"
                  action="{{ request()->fullUrlWithQuery([]) }}"
                  enctype="multipart/form-data"
            >
                {!! csrf_field() !!}
                {{-- load the view from the application if it exists, otherwise load the one in the package --}}
                @if(view()->exists('vendor.backpack.crud.form_content'))
                    @include('vendor.backpack.crud.form_content', [ 'fields' => $crud->fields(), 'action' => 'create' ])
                @else
                    @include('crud::form_content', [ 'fields' => $crud->fields(), 'action' => 'create' ])
                @endif

                <div class="card mt-2">
                    <div class="card-body row pt-3">
                        <div class="form-group col-sm-12">
                            <h6 class="p-2">
                                <span class="font-weight-bold">{{ count(request()->query()) }}</span>
                                @lang('export-operation::export.enabled_filters')
                                @if(count(request()->query()) >= 1)
                                    &rarr; <span class="font-weight-bold">(~{{ $query_count_filtered }} @lang('export-operation::export.entries'))</span>
                                @endif
                            </h6>
                            @if(count(request()->query()) >= 1)
                            <table class="table nowrap rounded card-table table-vcenter card-table shadow-xs border-xs">
                            <tbody>
                            <tr>
                                <th>
                                    @lang('export-operation::export.filters')
                                </th>
                                <th>
                                    @lang('export-operation::export.values')
                                </th>
                            </tr>
                            @if(collect(request()->query())->has('search'))
                                <tr>
                                    <td>
                                        @lang('backpack::crud.search')
                                    </td>
                                    <td>
                                        {{ request()->query()['search']['value'] }}
                                    </td>
                                </tr>
                            @endif
                            @foreach(request()->query() as $filterName => $value)
                                @php($currentFilter = $crud->filters()->where('name', $filterName)->first())
                                @continue(!$currentFilter || $filterName == 'search')
                                <tr>
                                    <td>
                                        {{ $currentFilter->label ?? $filterName }}
                                    </td>
                                    <td>
                                        {{ $currentFilter->values[$value] ?? $value }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                            @else
                                <div class="p-2">
                                    @lang('export-operation::export.no_filter')
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="{{ $config_disabled ? 'd-none' : '' }} card mt-2">
                    <div class="card-body row">
                        <div class="col-md-12 mb-4 d-flex justify-content-end">
                            <button type="button" id="btnIncludeAll"
                                    title="@lang('export-operation::export.include_all')"
                                    class="btn btn-success mr-2">
                                <span class="ladda-label">
                                    <i class="las la-check-circle"></i>
                                      @lang('export-operation::export.include_all')
                                </span>
                            </button>
                            <button type="button" id="btnExcludeAll"
                                    title="@lang('export-operation::export.exclude_all')"
                                    class="btn btn-danger">
                                <span class="ladda-label">
                                    <i class="las la-times-circle"></i>
                                    @lang('export-operation::export.exclude_all')
                                </span>
                            </button>
                        </div>
                        <div class="col-md-12">
                            <table
                                class="table  nowrap rounded card-table table-vcenter card-table shadow-xs border-xs">
                                <thead>
                                <tr>
                                    <th>
                                        @lang('export-operation::export.column')
                                    </th>
                                    <th>
                                        @lang('export-operation::export.include_in_export')
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($columns as $column)
                                    <tr>
                                        <td>
                                            {{ $column['label'] }}
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <label class="d-none" for="include_{{ $column['name'] }}">
                                                    @lang('export-operation::import.include_in_export')
                                                </label>
                                                @if($config_disabled)
                                                    <input readonly id="include_{{ $column['name'] }}"
                                                           name="include_{{ $column['name'] }}"
                                                           type="text" value="1">
                                                @else
                                                    <select id="include_{{ $column['name'] }}"
                                                            name="include_{{ $column['name'] }}"
                                                            class="form-control include-field">
                                                        <option value="1">
                                                            @lang('export-operation::export.include')
                                                        </option>
                                                        <option value="0">
                                                            @lang('export-operation::export.exclude')
                                                        </option>
                                                    </select>
                                                @endif
                                            </div>

                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- This makes sure that all field assets are loaded. --}}
                @if(class_exists(\Backpack\Basset\Facades\Basset::class))
                    <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>
                @else
                    <div class="d-none" id="parentLoadedAssets">{{ json_encode(Assets::loaded()) }}</div>
                @endif


                <button title="@lang('export-operation::export.confirm_export')"
                        class="btn btn-success">
                    <span class="ladda-label">
                        <i class="las la-file-download"></i>
                        @lang('export-operation::export.confirm_export')
                    </span>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script>
        $("#btnIncludeAll").on("click", (event) => {
            event.preventDefault();
            $(".include-field").val(1);
        });

        $("#btnExcludeAll").on("click", (event) => {
            event.preventDefault();
            $(".include-field").val(0);
        });
    </script>
@endpush

