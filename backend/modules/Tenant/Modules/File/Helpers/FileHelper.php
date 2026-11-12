<?php

namespace Modules\Tenant\Modules\File\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;

class FileHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "model"         => "required|string|in:content_group,content,answer,publication,training_content_group,training_content,training_answer,training_publication",
            "model_id"      => "required|numeric|exists:$request->model,id",

            "chunk"         => "required|file",
            "chunk_uid"     => "required|string",
            "chunk_name"    => "required|string",
            "chunk_total"   => "required|numeric",
            "chunk_number"  => "required|numeric",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateFile($file)
    {
        $mimes = SystemConfigurationHelper::getValueByKey('extensions_allowed_to_upload');
        $mimes = array_filter($mimes, function ($item) {
            return $item->permitted == true;
        });
        $mimes = array_values($mimes);
        $mimes = array_map(function ($item) {
            return $item->extension;
        }, $mimes);

        $size_mb = SystemConfigurationHelper::getValueByKey('maximum_file_size_to_upload');

        $file_size_bytes = $file->getSize();
        $file_size_mb = $file_size_bytes / (1024 * 1024);
        $file_size_mb = round($file_size_mb, 8);

        if ($file_size_mb > $size_mb) {
            throw new Exception('El archivo excede el tamaño máximo permitido');
        }

        $file_extension = $file->getClientOriginalExtension();

        if (!in_array($file_extension, $mimes)) {
            throw new Exception('Formato de archivo no admitido');
        }
    }

    public static function uploadChunk(Request $request)
    {
        // Obtenemos el archivo y la información necesaria
        $chunk = $request->file('chunk');
        $chunk_uid = $request->chunk_uid;
        $chunk_name = $request->chunk_name;
        $chunk_total = $request->chunk_total;
        $chunk_number = $request->chunk_number;

        // Creamos un directorio temporal para almacenar los fragmentos
        $temp_dir = storage_path('app/chunks/' . $chunk_uid);
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }

        // Movemos el fragmento al directorio temporal
        $chunk->move($temp_dir, $chunk_number);

        // Si todos los fragmentos han sido cargados
        if (self::allChunksUploaded($temp_dir, $chunk_total)) {
            // Unimos todos los fragmentos
            self::mergeChunks($temp_dir, $chunk_name, $chunk_total);

            // Eliminamos el directorio temporal
            self::deleteDirectory($temp_dir);

            // Obtenemos el archivo final y lo retornamos
            $file = self::getUploadedFile($chunk_name);

            return $file;
        }

        return 'Fragmento cargado exitosamente';
    }

    private static function allChunksUploaded($temp_dir, $chunk_total)
    {
        for ($i = 1; $i <= $chunk_total; $i++) {
            if (!file_exists($temp_dir . '/' . $i)) {
                return false;
            }
        }

        return true;
    }

    private static function mergeChunks($temp_dir, $chunk_name, $chunk_total)
    {
        $file_path = storage_path('app/uploads/' . $chunk_name);

        // Crea los directorios en la ruta si no existen
        $dir_path = dirname($file_path);
        if (!file_exists($dir_path)) {
            mkdir($dir_path, 0777, true);
        }

        // Abre el archivo final
        $file = fopen($file_path, 'wb');

        // Itera sobre los fragmentos
        for ($i = 1; $i <= $chunk_total; $i++) {
            // Abre el fragmento
            $chunk = fopen($temp_dir . '/' . $i, 'rb');

            // Lee el fragmento y escribe en el archivo final
            while ($buffer = fread($chunk, 4096)) {
                fwrite($file, $buffer);
            }

            // Cierra el fragmento
            fclose($chunk);
        }

        // Cierra el archivo final
        fclose($file);
    }

    private static function getUploadedFile($chunk_name)
    {
        $file_path = storage_path('app/uploads/' . $chunk_name);

        if (!file_exists($file_path)) {
            throw new Exception('Archivo no encontrado');
        }

        // Crea una nueva instancia de UploadedFile
        $file = new UploadedFile($file_path, $chunk_name);

        return $file;
    }

    public static function deleteUploadedFile($chunk)
    {
        $chunk_name = $chunk->getClientOriginalName();

        $file_path = storage_path('app/uploads/' . $chunk_name);

        self::deleteDirectory($file_path);
    }

    private static function deleteDirectory($dir_path)
    {
        if (!file_exists($dir_path)) {
            return;
        }

        if (!is_dir($dir_path)) {
            unlink($dir_path);
            return;
        }

        foreach (scandir($dir_path) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            self::deleteDirectory($dir_path . DIRECTORY_SEPARATOR . $item);
        }

        rmdir($dir_path);
    }
}
