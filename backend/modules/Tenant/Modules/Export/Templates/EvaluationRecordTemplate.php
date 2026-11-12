<?php

namespace Modules\Tenant\Modules\Export\Templates;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EvaluationRecordTemplate
{
    private const COLOR_CAPACITY = 'ff1f3864';
    private const COLOR_INDICATOR = 'ff2f5597';
    private const COLOR_WEEK = 'ffd9e2f3';
    private const COLOR_TYPE = 'ffeef3fb';
    private const COLOR_ACHIEVEMENT = 'fffff2cc';
    private const COLOR_FINAL = 'ffe2efda';
    private const COLOR_STRIPE = 'fff7f9fc';
    private const COLOR_LINE = 'ff8ea9db';
    private const COLOR_TEXT_LIGHT = 'ffffffff';

    private const WIDTH_NUMBER = 6;
    private const WIDTH_SCORE = 7.5;
    private const WIDTH_ACHIEVEMENT = 10;
    private const WIDTH_FINAL = 13;

    private const HEIGHT_CAPACITY = 32;
    private const HEIGHT_INDICATOR = 30;
    private const HEIGHT_WEEK = 20;
    private const HEIGHT_TYPE = 18;
    private const HEIGHT_ROW = 19;

    public static function template($sheet, $data)
    {
        $row = 1;

        $columnNumber = 2;
        $columnStudent = 3;
        $columnBlock = 4;

        $blockSpan = array_sum(array_column($data->capacities, 'span'));
        $columnFinal = $columnBlock + $blockSpan;
        $columnLast = Coordinate::stringFromColumnIndex($columnFinal);

        HeaderTemplate::addHeader($sheet, $data, $columnFinal - 1, $row);

        $row += 2;

        $coordinate = 'B' . $row;
        HeaderTemplate::addCell($sheet, $coordinate, $data->heading, 14, true, 'center', false);
        $range = $coordinate . ':' . $columnLast . $row;
        $sheet->mergeCells($range);
        $sheet->getRowDimension($row)->setRowHeight(26);
        self::fill($sheet, $range, self::COLOR_CAPACITY);
        $sheet->getStyle($range)->getFont()->getColor()->setARGB(self::COLOR_TEXT_LIGHT);

        $row++;

        $rowInitial = $row;

        foreach ($data->classroomColumns as $key => $column) {
            $coordinate = 'B' . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $column, 9, true, 'left', false);
            $sheet->mergeCells($coordinate . ':C' . $row);
            self::fill($sheet, $coordinate . ':C' . $row, self::COLOR_WEEK);

            $coordinate = 'D' . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $data->classroomRows[$key] ?? '-', 9, false, 'left', false);
            $sheet->mergeCells($coordinate . ':' . $columnLast . $row);

            $sheet->getRowDimension($row)->setRowHeight(17);
            $row++;
        }

        $row++;

        $rowCapacity = $row;
        $rowIndicator = $row + 1;
        $rowWeek = $row + 2;
        $rowType = $row + 3;

        $sheet->getRowDimension($rowCapacity)->setRowHeight(self::HEIGHT_CAPACITY);
        $sheet->getRowDimension($rowIndicator)->setRowHeight(self::HEIGHT_INDICATOR);
        $sheet->getRowDimension($rowWeek)->setRowHeight(self::HEIGHT_WEEK);
        $sheet->getRowDimension($rowType)->setRowHeight(self::HEIGHT_TYPE);

        self::addFixedColumn($sheet, $columnNumber, $rowCapacity, $rowType, 'N°');
        self::addFixedColumn($sheet, $columnStudent, $rowCapacity, $rowType, 'ESTUDIANTE');
        self::addFixedColumn($sheet, $columnFinal, $rowCapacity, $rowType, 'PROMEDIO FINAL');

        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnNumber))->setWidth(self::WIDTH_NUMBER);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnStudent))->setAutoSize(true);
        $sheet->getColumnDimension($columnLast)->setWidth(self::WIDTH_FINAL);
        self::fill($sheet, $columnLast . $rowCapacity, self::COLOR_CAPACITY);

        $index = $columnBlock;

        foreach ($data->capacities as $capacity) {
            self::addGroup(
                $sheet,
                $index,
                $capacity['span'],
                $rowCapacity,
                $capacity['short'] . '. ' . $capacity['name'],
                self::COLOR_CAPACITY,
                self::COLOR_TEXT_LIGHT
            );

            foreach ($capacity['indicators'] as $indicator) {
                self::addGroup(
                    $sheet,
                    $index,
                    $indicator['span'],
                    $rowIndicator,
                    $indicator['label'],
                    self::COLOR_INDICATOR,
                    self::COLOR_TEXT_LIGHT,
                    $indicator['name']
                );

                foreach ($indicator['weeks'] as $week) {
                    self::addGroup(
                        $sheet,
                        $index,
                        $week['span'],
                        $rowWeek,
                        $week['label'],
                        self::COLOR_WEEK,
                        null,
                        $week['title']
                    );

                    foreach ($week['contents'] as $content) {
                        $coordinate = Coordinate::stringFromColumnIndex($index) . $rowType;
                        HeaderTemplate::addCell($sheet, $coordinate, $content['label'], 9, true, 'center', false);
                        self::fill($sheet, $coordinate, self::COLOR_TYPE);
                        self::addComment($sheet, $coordinate, $content['title']);
                        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(self::WIDTH_SCORE);
                        $index++;
                    }
                }

                foreach (['LOGRO', 'R'] as $label) {
                    $columnLetter = Coordinate::stringFromColumnIndex($index);
                    $coordinate = $columnLetter . $rowWeek;
                    HeaderTemplate::addCell($sheet, $coordinate, $label, 9, true, 'center', false);
                    $sheet->mergeCells($coordinate . ':' . $columnLetter . $rowType);
                    self::fill($sheet, $coordinate, self::COLOR_ACHIEVEMENT);
                    $sheet->getColumnDimension($columnLetter)->setWidth(self::WIDTH_ACHIEVEMENT);
                    $index++;
                }
            }
        }

        $row = $rowType;

        foreach ($data->students as $indexStudent => $student) {
            $row++;
            $sheet->getRowDimension($row)->setRowHeight(self::HEIGHT_ROW);

            if (($indexStudent + 1) % 2 != 0) {
                self::fill(
                    $sheet,
                    Coordinate::stringFromColumnIndex($columnNumber) . $row . ':' . $columnLast . $row,
                    self::COLOR_STRIPE
                );
            }

            HeaderTemplate::addCell($sheet, Coordinate::stringFromColumnIndex($columnNumber) . $row, $student['number'], 10, false, 'center', false);
            HeaderTemplate::addCell($sheet, Coordinate::stringFromColumnIndex($columnStudent) . $row, $student['name'], 10, false, 'left');

            $index = $columnBlock;

            foreach ($data->capacities as $capacity) {
                foreach ($capacity['indicators'] as $indicator) {
                    foreach ($indicator['contents'] as $contentId) {
                        $coordinate = Coordinate::stringFromColumnIndex($index) . $row;
                        HeaderTemplate::addCell($sheet, $coordinate, $student['contents'][$contentId], 10, false, 'center', false);
                        $index++;
                    }

                    $coordinate = Coordinate::stringFromColumnIndex($index) . $row;
                    HeaderTemplate::addCell($sheet, $coordinate, $student['indicators'][$indicator['id']], 10, true, 'center', false);
                    self::fill($sheet, $coordinate, self::COLOR_ACHIEVEMENT);
                    $index++;

                    $coordinate = Coordinate::stringFromColumnIndex($index) . $row;
                    HeaderTemplate::addCell($sheet, $coordinate, $student['capacityRecoverScores'][$capacity['id']], 10, true, 'center', false);
                    self::fill($sheet, $coordinate, self::COLOR_ACHIEVEMENT);
                    $index++;
                }
            }

            HeaderTemplate::addCell($sheet, $columnLast . $row, $student['final'], 10, true, 'center', false);
            self::fill($sheet, $columnLast . $row, self::COLOR_FINAL);
        }

        $rowFinish = $row;

        self::addBorders($sheet, 'B' . $rowCapacity . ':' . $columnLast . $rowFinish);
        self::addOuterBorder($sheet, 'B' . $rowInitial . ':' . $columnLast . $rowFinish);

        $row += 2;

        foreach ($data->capacities as $capacity) {
            $coordinate = 'B' . $row;
            HeaderTemplate::addCell($sheet, $coordinate, $capacity['short'] . ': ' . $capacity['name'], 9, true, 'left', false);
            $sheet->mergeCells($coordinate . ':' . $columnLast . $row);
            $row++;

            foreach ($capacity['indicators'] as $indicator) {
                $coordinate = 'B' . $row;
                HeaderTemplate::addCell($sheet, $coordinate, '     ' . $indicator['label'] . ': ' . $indicator['name'], 9, false, 'left', false);
                $sheet->mergeCells($coordinate . ':' . $columnLast . $row);
                $row++;
            }
        }

        $sheet->freezePane(Coordinate::stringFromColumnIndex($columnBlock) . ($rowType + 1));

        return $sheet;
    }

    private static function addFixedColumn($sheet, int $index, int $rowStart, int $rowEnd, string $value)
    {
        $columnLetter = Coordinate::stringFromColumnIndex($index);
        $coordinate = $columnLetter . $rowStart;
        $range = $coordinate . ':' . $columnLetter . $rowEnd;

        HeaderTemplate::addCell($sheet, $coordinate, $value, 10, true, 'center', false);
        $sheet->mergeCells($range);
        self::fill($sheet, $range, self::COLOR_CAPACITY);
        $sheet->getStyle($range)->getFont()->getColor()->setARGB(self::COLOR_TEXT_LIGHT);
        self::center($sheet, $range);
    }

    private static function addGroup(
        $sheet,
        int $index,
        int $span,
        int $row,
        string $value,
        string $color,
        ?string $textColor = null,
        ?string $comment = null
    ) {
        $columnLetter = Coordinate::stringFromColumnIndex($index);
        $coordinate = $columnLetter . $row;
        $range = $span > 1
            ? $coordinate . ':' . Coordinate::stringFromColumnIndex($index + $span - 1) . $row
            : $coordinate;

        HeaderTemplate::addCell($sheet, $coordinate, $value, 9, true, 'center', false);

        if ($span > 1) {
            $sheet->mergeCells($range);
        }

        self::fill($sheet, $range, $color);
        self::center($sheet, $range);

        if ($textColor) {
            $sheet->getStyle($range)->getFont()->getColor()->setARGB($textColor);
        }

        if ($comment) {
            self::addComment($sheet, $coordinate, $comment);
        }
    }

    private static function fill($sheet, string $range, string $color)
    {
        $sheet->getStyle($range)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($color);
    }

    private static function center($sheet, string $range)
    {
        $sheet->getStyle($range)
            ->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private static function addComment($sheet, string $coordinate, string $text)
    {
        $sheet->getComment($coordinate)->getText()->createTextRun($text);
    }

    private static function addBorders($sheet, string $range)
    {
        $sheet->getStyle($range)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(self::COLOR_LINE);
    }

    private static function addOuterBorder($sheet, string $range)
    {
        $sheet->getStyle($range)
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()
            ->setARGB(Color::COLOR_BLACK);
    }
}
