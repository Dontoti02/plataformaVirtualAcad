<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

class AnswerHelper
{
    public static function validateUploadFile(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $answer = Answer::find($request->model_id);

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if (in_array($answer->status, [AnswerStatus::DELIVERED, AnswerStatus::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        $content = $answer->content;
        $classroom_id = $content->contentGroup->classroom_id;

        PeriodHelper::validateByClassroom($classroom_id);

        if (!$content->is_visible) {
            throw new Exception('El contenido aun no es visible');
        }

        if ($answer->status === AnswerStatus::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $result = (object) [
            'polymorphic' => $answer,
            'classroom_id' => $classroom_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $answer = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if (in_array($answer->status, [AnswerStatus::DELIVERED, AnswerStatus::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        if ($answer->status === AnswerStatus::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $content = $answer->content;
        $classroom_id = $content->contentGroup->classroom_id;

        PeriodHelper::validateByClassroom($classroom_id);
    }
}
