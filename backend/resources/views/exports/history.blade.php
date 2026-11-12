<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 1cm;
            size: portrait;
        }

        .divOne {
            width: 100%;
            height: 80px;
            position: relative;

            background-color: #e0f7fa;
        }

        .divTwo {
            width: 80%;
            height: 100%;

            position: relative;
        }

        .pOne {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;

            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .divThree {
            max-width: 20%;
            height: 100%;

            text-align: center;

            position: absolute;
            right: 0px;
            top: 0px;
        }

        .imgOne {
            height: 100%;
        }

        .h1One {
            text-align: center;
            font-size: 12pt;
        }

        .pTwo {
            text-align: right;
            font-size: 10pt;
        }

        .pThree {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
        }

        .pFour {
            text-align: center;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
            padding: 4px;
        }

        .pFive {
            text-align: right;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
            padding: 4px;
        }

        .pSix {
            text-align: left;
            font-size: 10pt;
            margin-top: 0;
            margin-bottom: 0;
            padding: 4px;
        }

        .tableOne {
            width: 100%;
            margin: auto;
            border-collapse: collapse;
        }

        .thOne {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .thOne,
        .tdOne {
            font-size: 10pt;
            padding: 4px;
        }
    </style>
</head>

<body>
    @include('exports/header')
    <h1 class="h1One">{{ $title }}</h1>
    <p class="pTwo">{{ $date }}</p>
    <p class="pThree"><strong>ALUMNO:</strong> {{ $student }}</p>
    <br>
    @foreach ($rows as $indexRows => $row)
        @if (count($row['courses']) == 0)
            @continue;
        @endif
        <br>
        <p class="pFour"><strong>SEMESTRE:</strong> {{ $row['name'] }}</p>
        <p class="pFive">
            <strong>PPS:</strong> {{ $row['semester_average'] }}
            <strong>PPA:</strong> {{ $row['accumulated_average'] }}
        </p>
        <table class="tableOne">
            <tr>
                @foreach ($columns as $key => $column)
                    <th class="thOne"
                        style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $column }}
                    </th>
                @endforeach
            </tr>
            @php
                $row['courses'] = array_map(function ($row) use ($columns) {
                    $row = array_intersect_key($row, $columns);
                    return array_merge($columns, $row);
                }, $row['courses']->toArray());
            @endphp
            @foreach ($row['courses'] as $index => $course)
                <tr
                    style="{{ $index + 1 == count($row['courses']) ? 'border-bottom: 1px solid #000;' : '' }}
            {{ ($index + 1) % 2 != 0 ? 'background-color: #f5f5f5;' : '' }}">
                    @foreach ($course as $key => $value)
                        <td class="tdOne"
                            style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                            {{ $key == 'id' ? $index + 1 : $value ?? 'SIN DATOS' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
        <p class="pSix"><strong>NRO. DE CURSOS:</strong> {{ count($row['courses']) }}</p>
        <br>
        @if ($indexRows % 2 != 0)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
