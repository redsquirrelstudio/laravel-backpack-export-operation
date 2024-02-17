<table>
    <thead>
    <tr>
        @foreach($config as $column)
            <th>
                {{ $column['label'] }}
            </th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($entries as $entry)
        <tr>
            @foreach($config as $column)
                <td>
                    @php
                        // create a list of paths to column blade views
                        // including the configured view_namespaces
                        $columnPaths = array_map(function($item) use ($column) {
                            return $item.'.'.$column['type'];
                        }, \Backpack\CRUD\ViewNamespaces::getFor('columns'));

                        // but always fall back to the stock 'text' column
                        // if a view doesn't exist
                        if (!in_array('crud::columns.text', $columnPaths)) {
                            $columnPaths[] = 'crud::columns.text';
                        }

                        $columnPath = collect($columnPaths)->filter(fn($path) => view()->exists($path))->first();
                        $content = view($columnPath, [
                            'entry' => $entry,
                            'column' => $column,
                            'crud' => $crud,
                        ]);
                        $escaped_content = html_entity_decode(strip_tags($content));
                    @endphp
                    {{ $escaped_content }}
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
