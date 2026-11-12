<?php

namespace Modules\Shared\Utils;

class MetadataFile { 

    public static function get($file) {

        $fileSizeBytes = $file->getSize();

        $fileName = $file->getClientOriginalName();
    
        $fileType = $file->getMimeType();
    
        $fileExtension = $file->getClientOriginalExtension();

        return [
            'size' => MetadataFile::fileSizeMB($fileSizeBytes),
            'unit'  => 'MB',
            'type' => $fileType,
            'originalName' => $fileName,
            'extension' => $fileExtension,
        ];
    }

    protected static function fileSizeMB($fileSizeBytes) {
        $fileSizeMB = $fileSizeBytes / (1024 * 1024);
        $fileSizeMB = round($fileSizeMB, 8);
        return $fileSizeMB;
    }

}