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
        }

        .tableHeaders {
            text-align: center;
            font-size: 10pt;
            padding: 4px;

            border-top: 1px solid #000;
            border-bottom: 1px solid #000;

            background-color: #e0f7fa;
        }

        .tableCell {
            text-align: left;
            font-size: 10pt;
            padding: 4px;

            border-left: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
    </style>
</head>

<body>
    @include('exports/header')
    <h1 class="h1One">{{ $title }}</h1>
    <p class="pTwo">{{ $date }}</p>
    @if ($studyProgram)
        <p class="pThree"><strong>PROGRAM DE ESTUDIOS:</strong> {{ $studyProgram }}</p>
    @endif
    @if ($rol)
        <p class="pThree"><strong>{{ $rol }}:</strong> {{ $person }}</p>
    @endif
    <p class="pThree"><strong>SEMESTRE:</strong> {{ $period }}</p>
    <br>
    <table class="tableOne">
        <tr>
            @foreach ($columns as $column)
                <th class="tableHeaders">{{ $column }}</th>
            @endforeach
        </tr>

        @foreach ($rows as $indexAzul => $valueAzul)
            <tr>
                @foreach ($valueAzul as $indexAmarillo => $valueAmarillo)
                    <?php
                    $rowspan = 1;
                    
                    $currentValue = isset($valueAmarillo[0]) ? $valueAmarillo[0] : '';
                    $currentColor = isset($valueAmarillo[1]) ? $valueAmarillo[1] : '#ffffff';
                    
                    if ($currentValue) {
                        for ($j = $indexAzul + 1; $j < count($rows); $j++) {
                            $nextValue = isset($rows[$j][$indexAmarillo][0]) ? $rows[$j][$indexAmarillo][0] : '';
                            if ($nextValue === $currentValue) {
                                $rowspan++;
                            } else {
                                break;
                            }
                        }
                    }
                    ?>

                    @if (!$currentValue || !isset($prev[$indexAmarillo]) || $prev[$indexAmarillo] != $currentValue)
                        <td class="tableCell" rowspan="{{ $rowspan }}" style="background-color: {{ $currentColor }}">
                            {!! $currentValue !!}
                        </td>
                    @endif

                    <?php $prev[$indexAmarillo] = $currentValue; ?>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>

</html>
