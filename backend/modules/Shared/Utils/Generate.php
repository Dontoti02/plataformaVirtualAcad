<?php

namespace Modules\Shared\Utils;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class Generate
{
    public static function generatePdf($view, $data, $name = '')
    {
        $paper = $data->paper ?? 'a4';
        $orientation = $data->orientation ?? null;

        $pdf = Pdf::class;
        $pdf = $orientation ? $pdf::setPaper($paper, $orientation) : $pdf::setPaper($paper);
        $pdf = $pdf->loadView($view, (array) $data);
        $binary = $pdf->output();

        $type = 'application/pdf';

        $filename = self::sanitizeFilename($data->filename ?? $data->title);
        $filename .= $name ? '-' . self::sanitizeFilename($name) : '';
        $filename .= '.pdf';

        return [$binary,$type, $filename];

    }

    public static function generatePdfOld($view, $data, $name = '')
    {
        $pdf = Pdf::class;
        $pdf = $pdf::setPaper('a4');
        $pdf = $pdf->loadView($view, (array) $data);
        $binary = $pdf->output();

        $filename = self::sanitizeFilename($data->title);
        $filename .= $name ? '-' . self::sanitizeFilename($name) : '';
        $filename .= '.pdf';

        return [$binary, $filename];

        // $temp_file = tmpfile();
        // fwrite($temp_file, $pdf->output());

        // $binary = file_get_contents(stream_get_meta_data($temp_file)['uri']);
        // $type = 'application/pdf';
        // $filename = self::sanitizeFilename($data->title);
        // $filename .= $name ? '-' . self::sanitizeFilename($name) : '';
        // $filename .= '.pdf';

        // return [$binary, $type, $filename];
    }

    public static function generateXlsx($template, $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet = $template::template($sheet, $data);
        $sheet->setShowGridlines(false);
        $sheet->setTitle($data->title);
        $sheet->getProtection()->setSheet($data->protectSheet ?? true);
        $writer = new Xlsx($spreadsheet);

        $temp_file = tmpfile();
        $writer->save(stream_get_meta_data($temp_file)['uri']);

        $binary = file_get_contents(stream_get_meta_data($temp_file)['uri']);
        $type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename = self::sanitizeFilename($data->filename ?? $data->title) . '.xlsx';

        return [$binary, $type, $filename];
    }

    public static function generateZip($files, $name)
    {
      $zip = new ZipArchive();
      
      $zipFilename = tempnam(sys_get_temp_dir(), 'zip');

      if ($zip->open($zipFilename, \ZipArchive::CREATE ) !== TRUE) {
        throw new Exception("No se pudo generar el archivo ZIP");
      }
      
      foreach ($files as $file) {
        list($binary, $filename) = $file;
        $zip->addFromString($filename, $binary);
      }

      $zip->close();

      $zipBinary = file_get_contents($zipFilename);

      $zipType = 'application/zip';

      $zipName = self::sanitizeFilename($name) . '.zip';

     
      return [$zipBinary, $zipType, $zipName];
    }

    public static function sanitizeFilename($filename)
    {
        // Convierte los caracteres especiales a caracteres ASCII
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);

        // Convierte el string a minúsculas
        $filename = strtolower($filename);

        // Reemplaza los espacios con guiones bajos
        $filename = str_replace(' ', '_', $filename);

        // Elimina los caracteres especiales
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '', $filename);

        return $filename;
    }
}
