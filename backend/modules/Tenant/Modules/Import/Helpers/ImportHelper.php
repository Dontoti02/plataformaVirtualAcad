<?php

namespace Modules\Tenant\Modules\Import\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportHelper
{
    public static function validateRequest($request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function extractData($file)
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        return $data;
    }

    /**
     * Nombres con los que el reporte de origen trae algunas columnas. En el de
     * notas la celda «LECTIVO» no existe: «PERIODO LECTIVO» está combinada
     * verticalmente con la fila de encabezados y esa celda llega vacía.
     */
    private const ALIASES = [
        'LECTIVO' => ['PERIODO LECTIVO'],
    ];

    public static function formatData($import, $import_detail, $data)
    {
        $log = json_decode($import_detail->log);
        $attributes = json_decode($import->attributes, true);

        // La fila de encabezados se busca, no se supone: cada reporte la trae en
        // una posición distinta y con celdas combinadas que llegan vacías.
        $headerIndex = self::findHeaderRow($data, $attributes);
        $columnsData = self::headerCells($data, $headerIndex);

        $title = '';
        foreach (array_slice($data, 0, $headerIndex) as $item) {
            if (!empty($item[0])) {
                $title = $item[0];
                break;
            }
        }

        $columns = [];
        foreach ($attributes as $key => $column) {

            $position = self::columnPosition($column, $columnsData);

            if ($position === false) {
                throw new Exception("Columna $column no encontrada.");
            }

            $columns[$key] = [$column, $position];
        }

        $rowsData = array_filter(array_slice($data, $headerIndex + 1, null, true), function ($value) {
            return !empty($value[0]);
        });

        $rows = [];
        foreach ($rowsData as $index => $item) {

            $row = [];
            $skip = false;

            // Número de fila tal como se ve en Excel.
            $id = $index + 1;
            $row['id'] = $id;

            foreach ($columns as $key => [$column, $position]) {
                if (!isset($item[$position]) || empty($item[$position])) {
                    if ($import->key === 'evaluations' && $column === 'NOTA') {
                        $item[$position] = 0.00;
                    } else {
                        $date = Carbon::now();
                        $log[] = "$date | Columna $column no encontrada o vacía en la fila $id";
                        $skip = true;
                        break;
                    }
                }

                $row[$key] = $item[$position];
            }

            if (!$skip) {
                $rows[] = (object) $row;
            }
        }

        $import->update(['title' => $title]);

        $date = Carbon::parse($import_detail->date);
        $now = Carbon::now();

        $import_detail->update([
            'progress' => 10,
            'time_elapsed' => $date->diffInMinutes($now),
            'log' => json_encode($log),
        ]);

        return $rows;
    }

    private static function normalize($value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value));
    }

    /**
     * Encabezados de la fila $index. Una celda vacía toma el valor de la de
     * arriba: así se leen los encabezados combinados en vertical.
     */
    private static function headerCells(array $data, int $index): array
    {
        $cells = [];
        foreach ($data[$index] as $position => $value) {
            $value = self::normalize($value);

            if ($value === '' && $index > 0) {
                $value = self::normalize($data[$index - 1][$position] ?? '');
            }

            $cells[$position] = $value;
        }

        return $cells;
    }

    private static function columnPosition(string $column, array $cells)
    {
        $column = self::normalize($column);

        foreach (array_merge([$column], self::ALIASES[$column] ?? []) as $name) {
            $position = array_search($name, $cells, true);

            if ($position !== false) {
                return $position;
            }
        }

        return false;
    }

    /**
     * La fila con más columnas esperadas, entre las primeras del archivo.
     */
    private static function findHeaderRow(array $data, array $attributes): int
    {
        $best = 0;
        $bestMatches = 0;

        foreach (array_slice($data, 0, 15, true) as $index => $item) {
            $cells = self::headerCells($data, $index);

            $matches = 0;
            foreach ($attributes as $column) {
                if (self::columnPosition($column, $cells) !== false) {
                    $matches++;
                }
            }

            if ($matches > $bestMatches) {
                $best = $index;
                $bestMatches = $matches;
            }
        }

        if ($bestMatches === 0) {
            throw new Exception('No se encontró la fila de encabezados. ¿Es el reporte correcto para esta importación?');
        }

        return $best;
    }
}
