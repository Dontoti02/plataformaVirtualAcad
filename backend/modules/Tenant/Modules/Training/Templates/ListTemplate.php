<?php

namespace Modules\Tenant\Modules\Training\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ListTemplate
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
        foreach ($data->filters as $key => $item) {
            if ($item) {
                $row++;
                $coordinate = 'B' . $row;
                $value = $key . ': ' . $item;
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false);
                $range =  $coordinate . ':' . $columnLast . $row;
                $sheet->mergeCells($range);
            }
        }

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
        foreach ($data->rows as $indexItem => $item) {
            $row++;
            foreach ($item as $key => $value) {
                if (!isset($indexes[$key])) continue;
                $columnLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
                $coordinate = $columnLetter . $row;
                $value = $value ?? '-';
                $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
                HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
            }

            if (($indexItem + 1) % 2 != 0) {
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

        // pFive
        $row++;
        $coordinate = 'B' . $row;
        $value = $data->footer;
        HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false);
        $range = $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);

        return $sheet;
    }
}
