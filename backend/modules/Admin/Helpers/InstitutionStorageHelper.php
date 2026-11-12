<?php

namespace Modules\Admin\Helpers;

use Modules\Shared\Services\CentralConfig;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Enum\MessageResponse;

class InstitutionStorageHelper
{
    /** Dónde vive institution_storage: local en single, central en el compartido. */
    protected static function connection(): ?string
    {
        return CentralConfig::isSingle() ? null : "central";
    }

    public static function find(int $institutionId)
    {

        $storage = DB::connection(self::connection())->table('institution_storage')
            ->where('institution_id', $institutionId)
            ->first();

        if (!$storage) {
            $message = str_replace("{var}", "espacio de almacenamiento", MessageResponse::notFound->value);
            throw new \Exception($message, 404);
        }

        return $storage;
    }

    public static function hasSpace($institutionId, $totalFileSizeMb)
    {

        $storageInfo = InstitutionStorageHelper::find($institutionId);

        $usedSpaceMb = $storageInfo->used_space_mb;
        $limitSpaceMb = $storageInfo->limit_space_mb;
        $newUsedSpaceMb = $usedSpaceMb + $totalFileSizeMb;

        return $newUsedSpaceMb <= $limitSpaceMb;
    }

    public static function updateUsedSpaceInstitution($institutionId, $totalFileSizeMb)
    {
        $storageInfo = InstitutionStorageHelper::find($institutionId);

        $usedSpaceMb = $storageInfo->used_space_mb;
        $limitSpaceMb = $storageInfo->limit_space_mb;
        // Nunca por debajo de cero: un contador desfasado no debe impedir borrar.
        $newUsedSpaceMb = max(0, $usedSpaceMb + $totalFileSizeMb);
        $newFreeSpaceMb = max(0, $limitSpaceMb - $newUsedSpaceMb);

        DB::connection(self::connection())->table('institution_storage')
            ->where('institution_id', $institutionId)
            ->update([
                'used_space_mb' => $newUsedSpaceMb,
                'free_space_mb' => $newFreeSpaceMb,
            ]);

        CentralConfig::flush();
    }
}
