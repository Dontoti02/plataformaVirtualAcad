<?php

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileSystemService
{

    /**
     * Antes estaba fijado a 'do-spaces'. Ahora sale de la configuración, para que
     * cada despliegue elija: disco local del VPS (single-tenant) u object storage
     * (el Latina Educa compartido).  El panel fija FILESYSTEM_DISK=local.
     */
    protected static function driver(): string
    {
        return config('filesystems.default');
    }

    public static function create(string $path, $file)
    {
        try {
            return Storage::disk(self::driver())->putFile($path, $file);
        } catch (\Exception $th) {
          Log::error('Error creating file: ' . $th->getMessage(), ['exception' => $th]);
          throw $th;
        }
    }

    public static function get(string $url)
    {
        try {
            return Storage::disk(self::driver())->get($url);
        } catch (\Exception $th) {
            Log::error('Error getting file: ' . $th->getMessage(), ['exception' => $th]);
            throw $th;
        }
    }

    public static function delete(string $path)
    {
        try {
            return Storage::disk(self::driver())->delete($path);
        } catch (\Exception $th) {
            throw $th;
        }
    }
}
