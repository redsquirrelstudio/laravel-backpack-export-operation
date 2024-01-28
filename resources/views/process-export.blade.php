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
            <div id="loading">
                <div class="card">
                    <div class="card-body row">
                        <div class="col-md-12 mb-4 d-flex align-items-center">
                            <h6 class="m-0">
                                @lang('export-operation::export.your_export_is_being_generated')
                            </h6>
                            <div class="ml-2 spinner-border text-primary spinner-border-sm"
                                 role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-none" id="complete">
                <div class="card">
                    <div class="card-body row">
                        <div class="col-md-12 mb-4">
                            <h6 class="m-0">
                                @lang('export-operation::export.your_export_is_complete')
                            </h6>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-item-center">
                    <a id="btnDownload"
                       class="btn btn-success mr-2"
                       download
                       title="@lang('export-operation::export.click_to_download')"
                       href="{{ url($crud->route.'/export/'.$entry->id.'/download') }}">
                        <span class="ladda-label">
                            <i class="las la-file-download"></i>
                             @lang('export-operation::export.click_to_download')
                        </span>
                    </a>
                    <a href="{{ url($crud->route) }}" class="btn btn-default">
                        <i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>
                            {{ $crud->entity_name_plural }}
                        </span>
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script>
        const handleLeave = function (e) {
            let confirmationMessage = 'Your export is being generated. '
                + 'If you leave before it is finished, it will be canceled.';

            (e || window.event).returnValue = confirmationMessage; //Gecko + IE
            return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
        }
        window.addEventListener("beforeunload", handleLeave);

        $(document).ready(() => {

            $.ajax({
                url: '{{ url($crud->route.'/export/'.$entry->id.'/start')  }}',
                type: 'POST',
                success: () => {
                    console.log("Export Operation :: Export Started");
                },
                error: (e) => {
                    window.location = '{{ url($crud->route) }}';
                }
            });

            const checker = setInterval(() => {
                $.ajax({
                    url: '{{ url($crud->route.'/export/'.$entry->id.'/check')  }}',
                    type: "GET",
                    success: (result) => {
                        if (result.complete) {
                            console.log("Export Operation :: Export Complete");
                            const downloadButton = document.getElementById('btnDownload');
                            downloadButton.setAttribute('download', result.file_name);
                            document.getElementById('loading').className = 'd-none';
                            document.getElementById('complete').className = 'd-block';
                            downloadButton.click();

                            clearInterval(checker);
                            window.removeEventListener("beforeunload", handleLeave);
                        }
                    },
                    error: (e) => {
                        window.location = '{{ url($crud->route) }}';
                    }
                });
            }, 2000);
        });
    </script>
@endpush
