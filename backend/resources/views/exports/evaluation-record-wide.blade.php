@php
    $scoreColumns = collect($capacities)->sum(fn($capacity) => collect($capacity['indicators'])->sum('span'));

    $scale = match (true) {
        $scoreColumns > 46 => 'xxs',
        $scoreColumns > 32 => 'xs',
        $scoreColumns > 20 => 'sm',
        default => 'md',
    };

    $studentWidth = $scoreColumns > 32 ? 12 : 16;
@endphp
<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 2.4cm 0.8cm 1.2cm 0.8cm;
        }

        @include('exports/partials/evaluation-record-styles')
    </style>
</head>

<body>
    <div class="header">
        <div class="header-bar">
            <p class="header-name">{{ $institutionName }}</p>
            @if ($institutionLogo)
                <img class="header-logo"
                    src="data:image/{{ mime_content_type(storage_path("app/$institutionLogo")) }};base64,{{ base64_encode(file_get_contents(storage_path("app/$institutionLogo"))) }}"
                    alt="Logo">
            @endif
        </div>
    </div>

    <h1 class="title">{{ $heading }}</h1>

    @include('exports/partials/evaluation-record-meta')

    <table class="grid {{ $scale }}">
        <thead>
            <tr>
                <th rowspan="4" class="lvl-fixed" style="width: 2.5%;">N°</th>
                <th rowspan="4" class="lvl-fixed student" style="width: {{ $studentWidth }}%;">ESTUDIANTE</th>
                @foreach ($capacities as $capacity)
                    <th colspan="{{ $capacity['span'] }}" class="lvl-capacity">
                        {{ $capacity['short'] }}. {{ $capacity['name'] }}
                    </th>
                @endforeach
                <th rowspan="4" class="lvl-fixed" style="width: 5%;">PROM. FINAL</th>
            </tr>

            <tr>
                @foreach ($capacities as $capacity)
                    @foreach ($capacity['indicators'] as $indicator)
                        <th colspan="{{ $indicator['span'] }}" class="lvl-indicator">
                            {{ $indicator['label'] }}
                        </th>
                    @endforeach
                @endforeach
            </tr>

            <tr>
                @foreach ($capacities as $capacity)
                    @foreach ($capacity['indicators'] as $indicator)
                        @foreach ($indicator['weeks'] as $week)
                            <th colspan="{{ $week['span'] }}" class="lvl-week">{{ $week['label'] }}</th>
                        @endforeach
                        <th rowspan="2" class="achievement">LOGRO</th>
                        <th rowspan="2" class="achievement">R</th>
                    @endforeach
                @endforeach
            </tr>

            <tr>
                @foreach ($capacities as $capacity)
                    @foreach ($capacity['indicators'] as $indicator)
                        @foreach ($indicator['weeks'] as $week)
                            @foreach ($week['contents'] as $content)
                                <th class="lvl-type">{{ $content['label'] }}</th>
                            @endforeach
                        @endforeach
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $indexStudent => $student)
                <tr class="{{ ($indexStudent + 1) % 2 != 0 ? 'striped' : '' }}">
                    <td>{{ $student['number'] }}</td>
                    <td class="student">{{ $student['name'] }}</td>
                    @foreach ($capacities as $capacity)
                        @foreach ($capacity['indicators'] as $indicator)
                            @foreach ($indicator['contents'] as $contentId)
                                <td>{{ $student['contents'][$contentId] ?? '-' }}</td>
                            @endforeach
                            <td class="achievement">{{ $student['indicators'][$indicator['id']] ?? '-' }}</td>
                            <td>{{ $student['capacityRecoverScores'][$capacity['id']] ?? '-' }}</td>
                        @endforeach
                    @endforeach
                    <td class="final">{{ $student['final'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">
        @foreach ($capacities as $capacity)
            <p><strong>{{ $capacity['short'] }}:</strong> {{ $capacity['name'] }}</p>
            @foreach ($capacity['indicators'] as $indicator)
                <p class="indent">{{ $indicator['label'] }}: {{ $indicator['name'] }}</p>
            @endforeach
        @endforeach
    </div>

    <table class="signatures">
        <tr>
            <td>DOCENTE</td>
            <td class="spacer"></td>
            <td>JEFE DE UNIDAD ACADÉMICA</td>
            <td class="spacer"></td>
            <td>DIRECCIÓN GENERAL</td>
        </tr>
    </table>
</body>

</html>
