<?php

namespace Modules\Tenant\Modules\Export\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ConsolidatedRegistrationTemplate
{
    public static function template($sheet, $data)
    {
        // Primera fila
        $row = 1;

        // Ultima columna
        $totalColumns = count($data->columns);
        $columnLast = Coordinate::stringFromColumnIndex($totalColumns + 1);

        HeaderTemplate::addHeader($sheet, $data, $totalColumns, $row);

        $row++;

        // h1One
        $row++;
        $coordinate = 'B' . $row;
        $value = $data->title;
        HeaderTemplate::addCell($sheet, $coordinate, $data->title, 12, true, 'center');
        $range =  $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);

        // pTwo
        $row++;
        $coordinate = 'B' . $row;
        $value = $data->date;
        HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, 'right');
        $range =  $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);

        // pThree
        $row++;
        $coordinate = 'B' . $row;
        $value = 'ALUMNO: ' . $data->studentDocumentNumber . ' - ' . $data->studentNames;
        HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false);
        $range =  $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);

        $row++;

        // tableOne
        // columns
        $row++;
        $index = 2;
        $indexes = [];
        foreach ($data->columns as $key => $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($index);
            $coordinate = $columnLetter . $row;
            $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
            HeaderTemplate::addCell($sheet, $coordinate, $column, 10, true, $alignment);
            $indexes[$key] = $index;
            $index++;
        }

        $range = 'B' . $row . ':' . $columnLast . $row;
        $borders = $sheet->getStyle($range)->getBorders();
        $borders->getTop()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        $borders->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        // rows
        $total = count($data->rows);
        foreach ($data->rows as $indexCourse => $course) {
            $row++;
            foreach ($course as $key => $value) {
                if (!isset($indexes[$key])) continue;
                $columnLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
                $coordinate = $columnLetter . $row;
                $value = $key == 'id' ? $indexCourse + 1 : $value ?? 'SIN DATOS';
                $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
            }

            if (($indexCourse + 1) % 2 != 0) {
                $sheet->getStyle('B' . $row . ':' . $columnLast . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('ff' . 'f5f5f5');
            }
        }

        $borders = $sheet->getStyle('B' . $row . ':' . $columnLast . $row)->getBorders();
        $borders->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        // pFour
        $row++;
        $coordinate = 'B' . $row;
        $value = 'NRO. DE MATRÍCULAS: ' . $total;
        HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false);
        $range = $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);

        return $sheet;
    }
}
