@if ($crud->hasAccess('export'))
    <a title="{{ __('export-operation::export.export_entity', ['entity' => $crud->entity_name_plural]) }}"
       href="{{ url($crud->route.'/export') }}" class="btn btn-secondary">
    <span class="ladda-label">
        <i class="las la-file-download"></i>
        {{ __('export-operation::export.export_entity', [
            'entity' => $crud->entity_name_plural
        ]) }}
    </span>
    </a>
@endif
