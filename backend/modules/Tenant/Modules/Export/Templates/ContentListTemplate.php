<?php

namespace Modules\Tenant\Modules\Export\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ContentListTemplate
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
            HeaderTemplate::addCell($sheet, $coordinate, $data->classroomRows[$key] ?? '-', 10, false);
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
            HeaderTemplate::addCell($sheet, $coordinate, $column, 10, true, $alignment);
            $indexes[$key] = $index;
            $index++;
        }

        // data
        foreach ($data->rows as $indexContent => $content) {
            $row++;
            foreach ($content as $key => $value) {
                if (!isset($indexes[$key])) continue;
                $columnLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
                $coordinate = $columnLetter . $row;

                $value = $value ?? '-';
                if ($key == 'id') {
                    $value = $indexContent + 1;
                } else if ($key == 'date' || $key == 'date_limit') {
                    $value = date('d/m/Y H:i', strtotime($value));
                }

                $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
            }

            if (($indexContent + 1) % 2 != 0) {
                $sheet->getStyle('B' . $row . ':' . $columnLast . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('ff' . 'f5f5f5');
            }
        }

        $range = 'B' . $rowInitial . ':' . $columnLast . $row;
        $borders = $sheet->getStyle($range)->getBorders();
        $borders->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        return $sheet;
    }
}
