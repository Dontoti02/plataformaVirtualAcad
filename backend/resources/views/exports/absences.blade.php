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
            font-size: 10pt;
            padding: 4px;
            text-align: left;
        }

        .thTwo {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .thTwo,
        .tdTwo {
            font-size: 10pt;
            padding: 4px
        }
    </style>
</head>

<body>
    @include('exports/header')
    <h1 class="h1One">{{ $title }}</h1>
    <br>
    <table class="tableOne">
        @foreach ($classroomColumns as $key => $column)
            <tr>
                <th class="thOne">
                    {{ $column }}
                </th>
                <th class="thOne" style="font-weight: normal;">
                    {{ $classroomRows[$key] }}
                </th>
            </tr>
        @endforeach
    </table>
    <table class="tableOne">
        <tr>
            @foreach ($columns as $key => $column)
                <th class="thTwo"
                    style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                    {{ $column }}
                </th>
            @endforeach
        </tr>
        @php
            $rows = array_map(function ($row) use ($columns) {
                $row = array_intersect_key($row, $columns);
                return array_merge($columns, $row);
            }, $rows);
        @endphp
        @foreach ($rows as $indexRows => $row)
            <tr
                style="{{ $indexRows + 1 == count($rows) ? 'border-bottom: 1px solid #000;' : '' }}
            {{ ($indexRows + 1) % 2 != 0 ? 'background-color: #f5f5f5;' : '' }}">
                @foreach ($row as $key => $value)
                    <td class="tdTwo"
                        style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $key == 'id'
                            ? $indexRows + 1
                            : (in_array($key, ['attended', 'absence', 'late'])
                                ? ($value == 1
                                    ? 'X'
                                    : '')
                                : $value ?? '') }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>

</html>
