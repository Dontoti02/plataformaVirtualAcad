<?php

namespace Modules\Tenant\Modules\Training\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class HeaderTemplate
{
    public static function addCell($sheet, $coordinate, $value, $size, $bold, $alignment = 'left', $autoSize = true)
    {
        $sheet->setCellValue($coordinate, $value);
        $sheet->getStyle($coordinate)->getFont()->setBold($bold);
        $sheet->getStyle($coordinate)->getFont()->setSize($size);
        $sheet->getColumnDimension(preg_replace('/[0-9]+/', '', $coordinate))->setAutoSize($autoSize);
        $sheet->getStyle($coordinate)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($coordinate)->getAlignment()->setHorizontal($alignment);
    }

    public static function addHeader($sheet, $data, int $totalColumns, &$row)
    {
        // Ultima columna
        $columnLast = Coordinate::stringFromColumnIndex($totalColumns + 1);

        // divOne
        $style = $sheet->getStyle('B2:' . $columnLast . '4');
        $style->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('ff' . 'e0f7fa');

        $style->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);

        // pOne
        $penultimateColumn = Coordinate::stringFromColumnIndex($totalColumns);

        $row++;
        $coordinate = 'B' . $row;
        $value = $data->institutionName;
        self::addCell($sheet, $coordinate, $value, 10, true);
        $row += 2;
        $range =  $coordinate . ':' . $penultimateColumn . $row;
        $sheet->mergeCells($range);

        // imgOne
        $coordinate = Coordinate::stringFromColumnIndex($totalColumns + 2) . ($row - 2);

        if ($data->institutionLogo) {
            $value = storage_path("app/$data->institutionLogo");

            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($value);
            $drawing->setHeight(50);
            $drawing->setCoordinates($coordinate);

            // Posicionamiento de la imagen
            $imageWidthInPoints = $drawing->getWidth();
            $offsetX = - ($imageWidthInPoints + 5);
            $drawing->setOffsetX($offsetX);
            $drawing->setOffsetY(5);

            $drawing->setWorksheet($sheet);
        }
    }
}
