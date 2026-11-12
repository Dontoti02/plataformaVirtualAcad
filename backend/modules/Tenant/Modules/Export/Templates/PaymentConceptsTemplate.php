<?php

namespace Modules\Tenant\Modules\Export\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PaymentConceptsTemplate
{
    public static function template($sheet, $data)
    {
        
 // Primera fila
 $row = 1;

 // Ultima columna
 $totalColumns = count($data->columns);
 $columnLast = Coordinate::stringFromColumnIndex($totalColumns + 1);

 // tableOne
 // columns
 $index = 1;
 $indexes = [];
 foreach ($data->columns as $key => $column) {
     $columnLetter = Coordinate::stringFromColumnIndex($index);
     $coordinate = $columnLetter . $row;
     $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
     HeaderTemplate::addCell($sheet, $coordinate, $column, 10, true, $alignment);
     $indexes[$key] = $index;
     $index++;
 }

 $range = 'A' . $row . ':' . $columnLast . $row;
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
 foreach ($data->rows as $indexStudent => $student) {
     $row++;
     foreach ($student->toArray() as $key => $value) {
         if (!isset($indexes[$key])) continue;
         $columnLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
         $coordinate = $columnLetter . $row;
         $value = $key == 'id' ? $indexStudent + 1 : $value ?? 'SIN DATOS';
         $alignment = in_array($key, $data->columnsAligned) ? 'center' : 'left';
         HeaderTemplate::addCell($sheet, $coordinate, $value, 10, false, $alignment);
     }

     if (($indexStudent + 1) % 2 != 0) {
         $sheet->getStyle('A' . $row . ':' . $columnLast . $row)
             ->getFill()
             ->setFillType(Fill::FILL_SOLID)
             ->getStartColor()
             ->setARGB('ff' . 'f5f5f5');
     }
 }

 $borders = $sheet->getStyle('A' . $row . ':' . $columnLast . $row)->getBorders();
 $borders->getBottom()
     ->setBorderStyle(Border::BORDER_THIN)
     ->getColor()
     ->setARGB(Color::COLOR_BLACK);

 return $sheet;
    }
}