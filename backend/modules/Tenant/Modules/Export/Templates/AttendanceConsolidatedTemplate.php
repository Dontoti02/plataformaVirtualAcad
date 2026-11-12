<?php

namespace Modules\Tenant\Modules\Export\Templates;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceConsolidatedTemplate
{
    public static function template($sheet, $data)
    {
        // Primera fila
        $row = 1;

        // Fechas de las asistencias
        $assistancesDates = array_keys((array)$data->rows);
        $assistancesDatesCount = count($assistancesDates);
        if ($assistancesDatesCount < 25) {
            $missingDatesCount = 25 - $assistancesDatesCount;
            $missingDates = array_fill(0, $missingDatesCount, "-1");
            $assistancesDates = array_merge($assistancesDates, $missingDates);
        }

        // Ultima columna
        $totalColumns = count($data->columns) + count($assistancesDates);
        $columnLast = Coordinate::stringFromColumnIndex($totalColumns);

        HeaderTemplate::addHeader($sheet, $data, $totalColumns - 1, $row);

        $row++;

        // h1One
        $row++;
        $coordinate = 'B' . $row;
        $value = $data->title;
        HeaderTemplate::addCell($sheet, $coordinate, $data->title, 12, true, 'center');
        $range =  $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);
        $borders = $sheet->getStyle($range)->getBorders();
        $borders->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        $row++;

        // tableOne
        // classroom columns
        $rowInitial = $row;
        foreach ($data->classroomColumns as $key => $column) {
            $coordinate = 'B' . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $column, 10, true);
            $range =  $coordinate . ':' . 'C' . $row;
            $sheet->mergeCells($range);

            $coordinate = 'D' . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $data->classroomRows[$key], 10, false);
            $range =  $coordinate . ':' . $columnLast . $row;
            $sheet->mergeCells($range);
            $row++;
        }

        // tableOne
        // columns
        $index = 2;
        $indexes = [];
        foreach ($data->columns as $key => $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($index);
            $coordinate = $columnLetter . $row;
            $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
            HeaderTemplate::addCell($sheet, $coordinate, $column, 10, true, $alignment, ($key == 'id' || $key == 'person'));

            if ($key == 'assistance') {
                $range =  $coordinate . ':' . $columnLast . $row;
                $sheet->mergeCells($range);
            } else {
                $range =  $coordinate . ':' . $columnLetter . ($row + 1);
                $sheet->mergeCells($range);
            }

            $indexes[$key] = $index;
            $index++;
        }
        $row++;

        // dates
        $index = 4;
        foreach ($assistancesDates as $date) {
            $formattedDate = $date != "-1" ? date('d/m/Y', strtotime($date)) : "";
            $columnLetter = Coordinate::stringFromColumnIndex($index);
            $coordinate = $columnLetter . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $formattedDate, 10, true, 'center', $date != "-1");
            $sheet->getStyle($coordinate)->getAlignment()->setTextRotation(90);

            if ($date == "-1") {
                $sheet->getColumnDimension($columnLetter)->setWidth(3.5);
            }

            $index++;
        }
        $rowAssistance = $row;

        // participants
        foreach ($data->participants as $indexparticipant => $participant) {
            $row++;
            foreach ($participant as $key => $value) {
                if (!isset($indexes[$key])) continue;
                $columnLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
                $coordinate = $columnLetter . $row;
                $value = $key == 'id' ? $indexparticipant + 1 : $value ?? 'SIN DATOS';
                $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
            }

            if (($indexparticipant + 1) % 2 != 0) {
                $sheet->getStyle('B' . $row . ':' . $columnLast . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('ff' . 'f5f5f5');
            }
        }
        $rowFinish = $row;

        // rows
        $index = 4;
        foreach ($assistancesDates as $indexDate => $assistancesDate) {
            $columnLetter = Coordinate::stringFromColumnIndex($indexDate + 4);
            $row = $rowAssistance;

            if (!isset($data->rows[$assistancesDate])) continue;
            foreach ($data->participants as $indexparticipant => $participant) {
                $row++;
                $assistanceParticipant = array_filter($data->rows[$assistancesDate], function ($row) use ($participant) {
                    return $row['person_id'] == $participant['id'];
                });
                $assistanceParticipant = reset($assistanceParticipant);
                Log::info($assistanceParticipant);
                $coordinate = $columnLetter . $row;
                $value = $assistanceParticipant ? $assistanceParticipant["assistance"] : '';
                $alignment = 'center';
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
            }
        }

        // Minimo de columnas
        $minColumns = 28;

        if ($totalColumns < $minColumns) {
            $columnLetter = Coordinate::stringFromColumnIndex($totalColumns + 1);
            $sheet->insertNewColumnBefore($columnLetter, $minColumns - $totalColumns);

            $totalColumns = $minColumns;
            $columnLast = Coordinate::stringFromColumnIndex($minColumns);
        }

        $range = 'B' . $rowInitial . ':' . $columnLast . $rowFinish;
        $borders = $sheet->getStyle($range)->getBorders();
        $borders->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        return $sheet;
    }
}
