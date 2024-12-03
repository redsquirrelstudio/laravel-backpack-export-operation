@if ($crud->hasAccess('export'))
    @php
        $id = 'export-button-' . Illuminate\Support\Str::random()
    @endphp

    <a title="{{ __('export-operation::export.export_entity', ['entity' => $crud->entity_name_plural]) }}"
       href="{{ url($crud->route.'/export') }}"
       class="btn btn-secondary"
       style="display: none"
       id="{{ $id }}">

            <i class="las la-file-download"></i>
            {{ __('export-operation::export.export_entity', [
                'entity' => $crud->entity_name_plural
            ]) }}

    </a>

    @push('after_scripts')
        <script>
            jQuery(document).ready(function($) {
                var $exportButtonElement = $("#{{ $id }}");

                $exportButtonElement.on("click", function (event) {
                    var $searchInput = $('#crudTable_filter input');
                    var searchFragment = '';

                    event.preventDefault();

                    // there is a search bar and it has a value
                    if ($searchInput.length && $searchInput.val().length > 0) {
                        searchFragment = '&search[value]=' + $searchInput.val();
                    }

                    window.location = event.target.href + '?' + window.location.search.replace('?' , '') + searchFragment;
                });

                {{-- show the element only after the event listener has been set --}}
                $exportButtonElement.show();
            });
        </script>
    @endpush
@endif
