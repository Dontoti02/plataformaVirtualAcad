@php
    $columnsOf = function ($capacity) {
        return collect($capacity['indicators'])->sum('span');
    };

    $widestSection = collect($capacities)->map($columnsOf)->max() ?? 0;
    $summaryColumns = count($capacities);

    $scale = $widestSection > 26 ? 'xs' : ($widestSection > 16 ? 'sm' : 'md');
@endphp
<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 2.4cm 0.9cm 1.2cm 0.9cm;
            size: landscape;
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

    @foreach ($capacities as $indexCapacity => $capacity)
        <div class="{{ $indexCapacity == 0 ? 'section-first' : 'section' }}">
            <h1 class="title">{{ $heading }}</h1>

            @include('exports/partials/evaluation-record-meta')

            <p class="section-title">{{ $capacity['short'] }}. {{ $capacity['name'] }}</p>

            <table class="grid {{ $scale }}">
                <thead>
                    <tr>
                        <th rowspan="3" class="lvl-fixed" style="width: 4%;">N°</th>
                        <th rowspan="3" class="lvl-fixed student" style="width: 21%;">ESTUDIANTE</th>
                        @foreach ($capacity['indicators'] as $indicator)
                            <th colspan="{{ $indicator['span'] }}" class="lvl-indicator">
                                {{ $indicator['label'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($capacity['indicators'] as $indicator)
                            @foreach ($indicator['weeks'] as $week)
                                <th colspan="{{ $week['span'] }}" class="lvl-week">{{ $week['label'] }}</th>
                            @endforeach
                            <th rowspan="2" class="achievement">LOGRO</th>
                            <th rowspan="2" class="achievement">R</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($capacity['indicators'] as $indicator)
                            @foreach ($indicator['weeks'] as $week)
                                @foreach ($week['contents'] as $content)
                                    <th class="lvl-type">{{ $content['label'] }}</th>
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
                            @foreach ($capacity['indicators'] as $indicator)
                                @foreach ($indicator['contents'] as $contentId)
                                    <td>{{ $student['contents'][$contentId] ?? '-' }}</td>
                                @endforeach
                                <td class="achievement">{{ $student['indicators'][$indicator['id']] ?? '-' }}</td>
                                <td>{{ $student['capacityRecoverScores'][$capacity['id']] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="legend">
                @foreach ($capacity['indicators'] as $indicator)
                    <p class="indent">{{ $indicator['label'] }}: {{ $indicator['name'] }}</p>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="{{ count($capacities) ? 'section' : 'section-first' }}">
        <h1 class="title">{{ $heading }}</h1>

        @include('exports/partials/evaluation-record-meta')

        <p class="section-title">CONSOLIDADO FINAL</p>

        <table class="grid md">
            <thead>
                <tr>
                    <th class="lvl-fixed" style="width: 4%;">N°</th>
                    <th class="lvl-fixed student" style="width: 34%;">ESTUDIANTE</th>
                    @foreach ($capacities as $capacity)
                        <th class="lvl-indicator">{{ $capacity['short'] }}</th>
                    @endforeach
                    <th class="lvl-fixed" style="width: 14%;">PROMEDIO FINAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $indexStudent => $student)
                    <tr class="{{ ($indexStudent + 1) % 2 != 0 ? 'striped' : '' }}">
                        <td>{{ $student['number'] }}</td>
                        <td class="student">{{ $student['name'] }}</td>
                        @foreach ($capacities as $capacity)
                            <td class="achievement">{{ $student['capacities'][$capacity['id']] ?? '-' }}</td>
                        @endforeach
                        <td class="final">{{ $student['final'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="legend">
            @foreach ($capacities as $capacity)
                <p>{{ $capacity['short'] }}: {{ $capacity['name'] }}</p>
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
    </div>
</body>

</html>
