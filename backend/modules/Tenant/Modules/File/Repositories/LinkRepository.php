<?php

namespace Modules\Tenant\Modules\File\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\Helpers\AnswerHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ContentHelper;
use Modules\Tenant\Modules\Classroom\Helpers\PublicationHelper;
use Modules\Tenant\Modules\File\Helpers\LinkHelper;
use Modules\Tenant\Modules\File\Models\Link;
use Modules\Tenant\Modules\Training\Helpers\TrainingAnswerHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingContentHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingPublicationHelper;

class LinkRepository
{
    public static function listByModel($polymorphic)
    {
        $links = $polymorphic->links()
            ->select('id', 'url')
            ->get();

        return $links;
    }

    public static function create(Request $request)
    {
        LinkHelper::validateRequest($request);

        switch ($request->model) {
            case 'content':
                $aux = ContentHelper::validateUploadFile($request);
                break;
            case 'answer':
                $aux = AnswerHelper::validateUploadFile($request);
                break;
            case 'publication':
                $aux = PublicationHelper::validateUploadFile($request);
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

        $aux->polymorphic->links()->create(['url' => $request->link]);

        $result = self::listByModel($aux->polymorphic);

        return $result;
    }

    public static function delete(int $id, string $model)
    {
        if (!in_array($model, ['content', 'answer', 'publication', 'training_content', 'training_answer', 'training_publication'])) {
            throw new Exception('Modelo no admitido');
        }

        $link = Link::byKey('id', $id);

        switch ($model) {
            case 'content':
                ContentHelper::validateDelete('link', $link);
                break;
            case 'answer':
                AnswerHelper::validateDelete('link', $link);
                break;
            case 'publication':
                PublicationHelper::validateDelete('link', $link);
                break;
            case 'training_content':
                TrainingContentHelper::validateDelete('link', $link);
                break;
            case 'training_answer':
                TrainingAnswerHelper::validateDelete('link', $link);
                break;
            case 'training_publication':
                TrainingPublicationHelper::validateDelete('link', $link);
                break;
            default:
                throw new Exception('Modelo no admitido');
        }

        $link->delete();

        return 'Enlace eliminado correctamente';
    }
}
