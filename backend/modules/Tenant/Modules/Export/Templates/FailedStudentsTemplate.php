<?php

namespace Modules\Tenant\Modules\Export\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FailedStudentsTemplate
{
    private const COLOR_PRIMARY = 'ff1a365d';
    private const COLOR_TEXT_LIGHT = 'ffffffff';
    private const COLOR_STRIPE = 'fff8fafc';
    private const COLOR_ALERT = 'fffef2f2';
    private const COLOR_ALERT_TEXT = 'ff991b1b';

    private const COLUMN_WIDTHS = [
        'index' => 6,
        'document_number' => 13,
        'student_name' => 34,
        'study_program_name' => 26,
        'cycle_name' => 9,
        'section_shift' => 22,
        'course_name' => 34,
        'teacher_name' => 30,
        'score' => 10,
        'status' => 16,
    ];

    public static function template($sheet, $data)
    {
        $row = 1;
        $totalColumns = count($data->columns);
        $columnLast = Coordinate::stringFromColumnIndex($totalColumns + 1);

        // Header institucional
        HeaderTemplate::addHeader($sheet, $data, $totalColumns, $row);

        $row += 2;

        // Título del reporte
        $coordinate = 'B' . $row;
        HeaderTemplate::addCell($sheet, $coordinate, $data->title, 13, true, 'center', false);
        $range = $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);
        $sheet->getRowDimension($row)->setRowHeight(28);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB(self::COLOR_PRIMARY);
        $sheet->getStyle($range)->getFont()->getColor()->setARGB(self::COLOR_TEXT_LIGHT);

        $row += 2;

        // Metadatos y filtros aplicados
        if (!empty($data->filters)) {
            foreach ($data->filters as $label => $val) {
                $coordLabel = 'B' . $row;
                HeaderTemplate::addCell($sheet, $coordLabel, $label . ':', 9, true, 'left', false);
                $sheet->mergeCells($coordLabel . ':C' . $row);

                $coordVal = 'D' . $row;
                HeaderTemplate::addCell($sheet, $coordVal, (string) $val, 9, false, 'left', false);
                $sheet->mergeCells($coordVal . ':' . $columnLast . $row);
                $row++;
            }
            $row++;
        }

        // Resumen de Métricas (KPIs)
        if (!empty($data->summary)) {
            $coordKpi = 'B' . $row;
            HeaderTemplate::addCell($sheet, $coordKpi, 'RESUMEN ACADÉMICO', 10, true, 'left', false);
            $sheet->mergeCells($coordKpi . ':' . Coordinate::stringFromColumnIndex(min($totalColumns + 1, 6)) . $row);
            $row++;

            $kpis = [
                'Total Evaluados' => $data->summary['total_students'] ?? 0,
                'Aprobados' => $data->summary['total_approved'] ?? 0,
                'No Aprobados' => $data->summary['total_failed'] ?? 0,
                '% Desaprobación' => ($data->summary['failure_rate'] ?? 0) . '%',
                'Unidades Didácticas' => $data->summary['total_classrooms'] ?? 0,
            ];

            $kpiCol = 2;
            foreach ($kpis as $kpiLabel => $kpiValue) {
                $cLet = Coordinate::stringFromColumnIndex($kpiCol);
                HeaderTemplate::addCell($sheet, $cLet . $row, $kpiLabel, 9, true, 'center', false);
                HeaderTemplate::addCell($sheet, $cLet . ($row + 1), (string) $kpiValue, 11, true, 'center', false);
                $kpiRange = $cLet . $row . ':' . $cLet . ($row + 1);
                $sheet->getStyle($kpiRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($kpiLabel === 'No Aprobados' || $kpiLabel === '% Desaprobación' ? self::COLOR_ALERT : 'fff1f5f9');
                $sheet->getStyle($kpiRange)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()
                    ->setARGB('ffcbd5e1');
                $kpiCol++;
            }
            $row += 3;
        }

        // Cabeceras de la tabla
        $rowHeader = $row;
        $colIdx = 2;
        $indexes = [];
        foreach ($data->columns as $key => $columnTitle) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $coord = $colLetter . $row;
            $align = in_array($key, $data->columnsAligned ?? []) ? 'center' : 'left';
            HeaderTemplate::addCell($sheet, $coord, $columnTitle, 9, true, $align, false);
            $indexes[$key] = $colIdx;
            $colIdx++;
        }

        $headerRange = 'B' . $rowHeader . ':' . $columnLast . $rowHeader;
        $sheet->getRowDimension($rowHeader)->setRowHeight(24);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB(self::COLOR_PRIMARY);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB(self::COLOR_TEXT_LIGHT);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);

        // Filas de datos
        if (empty($data->rows)) {
            $row++;
            $sheet->getRowDimension($row)->setRowHeight(24);
            HeaderTemplate::addCell($sheet, 'B' . $row, 'No se registraron estudiantes desaprobados bajo los filtros seleccionados.', 9, false, 'center', false);
            $sheet->mergeCells('B' . $row . ':' . $columnLast . $row);
        } else {
            foreach ($data->rows as $rIdx => $student) {
                $row++;
                $sheet->getRowDimension($row)->setRowHeight(22);

                if ($rIdx % 2 !== 0) {
                    $sheet->getStyle('B' . $row . ':' . $columnLast . $row)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(self::COLOR_STRIPE);
                }

                foreach ($student as $key => $val) {
                    if (!isset($indexes[$key])) continue;
                    $colLetter = Coordinate::stringFromColumnIndex($indexes[$key]);
                    $coord = $colLetter . $row;
                    $align = in_array($key, $data->columnsAligned ?? []) ? 'center' : 'left';
                    $cellVal = $key === 'index' ? ($rIdx + 1) : ($val ?? '-');

                    HeaderTemplate::addCell($sheet, $coord, (string) $cellVal, 9, ($key === 'score' || $key === 'status'), $align, false);
                    $sheet->getStyle($coord)->getAlignment()->setWrapText(true);

                    if ($key === 'score' || $key === 'status') {
                        $sheet->getStyle($coord)->getFont()->getColor()->setARGB(self::COLOR_ALERT_TEXT);
                        $sheet->getStyle($coord)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB(self::COLOR_ALERT);
                    }
                }
            }
        }

        // Anchos explícitos para evitar columnas comprimidas
        foreach ($indexes as $key => $cIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($cIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(false);
            $sheet->getColumnDimension($colLetter)->setWidth(self::COLUMN_WIDTHS[$key] ?? 18);
        }

        // Bordes de la tabla completa
        if ($row >= $rowHeader) {
            $tableRange = 'B' . $rowHeader . ':' . $columnLast . $row;
            $sheet->getStyle($tableRange)->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setARGB('ffcbd5e1');
        }

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        return $sheet;
    }
}
