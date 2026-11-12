<?php

namespace Modules\Tenant\Modules\File\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\File\Models\File;
use Modules\Shared\Services\FileSystemService;
use Modules\Tenant\Modules\Classroom\Helpers\AnswerHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ContentGroupHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ContentHelper;
use Modules\Tenant\Modules\Classroom\Helpers\PublicationHelper;
use Modules\Tenant\Modules\File\Helpers\FileHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingAnswerHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingContentGroupHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingContentHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingPublicationHelper;
use Modules\Tenant\Services\FileTenantService;

class FileRepository
{
    public static function listByModel($polymorphic)
    {
        $files = $polymorphic->files()
            ->select('id', 'name as uuid', 'url', 'metadata')
            ->get()
            ->each(function ($file) {
                $file->metadata = json_decode($file->metadata);
            });

        return $files;
    }

    public static function download(string $uuid)
    {
        $file = File::byKey('name', $uuid);

        $metadata = json_decode($file->metadata);

        $type = $metadata->type;
        $filename = $metadata->originalName;

        $binary = FileSystemService::get($file->url);

        return [$binary, $type, $filename];
    }

    public static function upload(Request $request)
    {
        FileHelper::validateRequest($request);

        switch ($request->model) {
            case 'content_group':
                $aux = ContentGroupHelper::validateUploadFile($request);
                break;
            case 'content':
                $aux = ContentHelper::validateUploadFile($request);
                break;
            case 'answer':
                $aux = AnswerHelper::validateUploadFile($request);
                break;
            case 'publication':
                $aux = PublicationHelper::validateUploadFile($request);
                break;
            case 'training_content_group':
                $aux = TrainingContentGroupHelper::validateUploadFile($request);
                break;
            case 'training_content':
                $aux = TrainingContentHelper::validateUploadFile($request);
                break;
            case 'training_answer':
                $aux = TrainingAnswerHelper::validateUploadFile($request);
                break;
            case 'training_publication':
                $aux = TrainingPublicationHelper::validateUploadFile($request);
                break;
            default:
                throw new Exception('Modelo no admitido');
        }

        $file = FileHelper::uploadChunk($request);

        if (is_string($file)) {
            return $file;
        }

        FileHelper::validateFile($file);

        $prefix = strpos($request->model, 'training_') === 0 ? 'training_' : 'classroom_';
        $id = $prefix === 'training_' ? $aux->training_id : $aux->classroom_id;
        $path = $prefix . $id . '/' . $request->model . '_' . $request->model_id;

        FileTenantService::save($aux->polymorphic, $file, $path);
        FileHelper::deleteUploadedFile($file);

        $result = self::listByModel($aux->polymorphic);

        return $result;
    }

    public static function delete(int $id, string $model)
    {
        if (!in_array($model, ['content_group', 'content', 'answer', 'publication', 'training_content_group', 'training_content', 'training_answer', 'training_publication'])) {
            throw new Exception('Modelo no admitido');
        }

        $file = File::byKey('id', $id);

        switch ($model) {
            case 'content_group':
                ContentGroupHelper::validateDelete('file', $file);
                break;
            case 'content':
                ContentHelper::validateDelete('file', $file);
                break;
            case 'answer':
                AnswerHelper::validateDelete('file', $file);
                break;
            case 'publication':
                PublicationHelper::validateDelete('file', $file);
                break;
            case 'training_content_group':
                TrainingContentGroupHelper::validateDelete('file', $file);
                break;
            case 'training_content':
                TrainingContentHelper::validateDelete('file', $file);
                break;
            case 'training_answer':
                TrainingAnswerHelper::validateDelete('file', $file);
                break;
            case 'training_publication':
                TrainingPublicationHelper::validateDelete('file', $file);
                break;
            default:
                throw new Exception('Modelo no admitido');
        }

        FileTenantService::delete([$file]);

        return 'Archivo eliminado correctamente';
    }
}
