<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 1cm;
            size: landscape;
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
            border-bottom: 1px solid #000;
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

        .row-date th {
            text-align: center;
            vertical-align: middle;
            margin-block: 10px;
        }

        .row-date th p {
            transform: rotate(270deg);
            transform-origin: bottom left;
            margin: 25px 0px;
            width: 20px;
            text-align: center;
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
        @php
            $assistancesDates = array_keys((array) $rows);
            $assistancesDatesCount = count($assistancesDates);
        @endphp
        <tr>
            @foreach ($columns as $key => $column)
                @if ($key != 'assistance')
                    <th class="thTwo" rowspan="2"
                        style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $column }}
                    </th>
                @else
                    <th class="thTwo" colspan="{{ $assistancesDatesCount }}" rowspan="1"
                        style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $column }}
                    </th>
                @endif
            @endforeach

        </tr>
        <tr class="row-date">
            @foreach ($assistancesDates as $date)
                <th class="thTwo">
                    <p>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
                </th>
            @endforeach
        </tr>
        @foreach ($participants as $indexparticipant => $participant)
            <tr style="{{ ($indexparticipant + 1) % 2 != 0 ? 'background-color: #f5f5f5;' : '' }}">
                @foreach ($participant as $key => $value)
                    <td style="{{ in_array($key, $columnsAligned) ? 'text-align: center;' : 'text-align: left;' }}">
                        {{ $key == 'id' ? $indexparticipant + 1 : $value ?? 'SIN DATOS' }}
                    </td>
                @endforeach
                @foreach ($assistancesDates as $indexDate => $assistancesDate)
                    @php
                        $assistanceParticipant = array_filter($rows[$assistancesDate], function ($row) use (
                            $participant,
                        ) {
                            return $row['person_id'] == $participant['id'];
                        });
                        $assistanceParticipant = reset($assistanceParticipant);
                    @endphp
                    <td style="text-align: center;">
                        {{ $assistanceParticipant ? $assistanceParticipant['assistance'] : '' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>

</html>
