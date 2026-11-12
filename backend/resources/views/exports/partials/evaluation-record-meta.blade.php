@php
    $pairs = collect($classroomColumns)
        ->map(fn($label, $key) => ['label' => $label, 'value' => $classroomRows[$key] ?? '-'])
        ->values()
        ->chunk(2);
@endphp

<table class="meta">
    @foreach ($pairs as $pair)
        <tr>
            @foreach ($pair as $item)
                <th>{{ $item['label'] }}</th>
                <td>{{ $item['value'] }}</td>
            @endforeach
            @if ($pair->count() == 1)
                <th></th>
                <td></td>
            @endif
        </tr>
    @endforeach
</table>
