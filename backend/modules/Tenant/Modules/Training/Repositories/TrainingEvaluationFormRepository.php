<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Modules\Training\Helpers\TrainingEvaluationFormHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingForm;
use Modules\Tenant\Modules\Training\Models\TrainingQuestionType;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingEvaluationFormRepository
{
    public static function questionTypes()
    {
        $questionTypes = TrainingQuestionType::select()
            ->orderBy('order_number', 'asc')
            ->get();

        return $questionTypes;
    }

    public static function set(TrainingContent $content, $request, bool $isUpdate)
    {
        TrainingEvaluationFormHelper::validateRequest($request, $isUpdate);
        $request = (object) $request;

        $scoreMax = $request->score_max;
        $scoreMaxQuestions = array_sum(array_column($request->questions, 'score_max'));

        if ($content->score != $scoreMax) {
            throw new Exception("El puntaje máximo del formulario no coincide con el puntaje máximo de la evaluación");
        }

        if ($scoreMax != $scoreMaxQuestions) {
            throw new Exception("La suma de los puntajes de las preguntas no coincide con el puntaje máximo del formulario");
        }

        $form = TrainingForm::select()
            ->where('training_content_id', $content->id)
            ->first();

        if ($form) {
            $isModified = $form->title != $request->title ||
                $form->description != $request->description ||
                $form->score_max != $request->score_max;

            $questionKeysRequest = array_column($request->questions, 'key');
            $questionKeysForm = $form->questions->pluck('key')->toArray();
            $isModified |= count(array_diff($questionKeysRequest, $questionKeysForm)) > 0 ||
                count(array_diff($questionKeysForm, $questionKeysRequest)) > 0;

            if (!$isModified) {
                foreach ($request->questions as $questionRequest) {
                    $questionForm = $form->questions->where('key', $questionRequest['key'])->first();
                    if ($questionForm) {
                        $isModified |= $questionForm->training_question_type_key != $questionRequest['training_question_type_key'] ||
                            $questionForm->label != $questionRequest['label'] ||
                            $questionForm->order_number != $questionRequest['order_number'] ||
                            $questionForm->score_max != $questionRequest['score_max'] ||
                            json_encode($questionForm->options) != json_encode($questionRequest['options']);
                    } else {
                        $isModified = true;
                    }

                    if ($isModified) {
                        break;
                    }
                }
            }

            if ($isModified && $form->responses()->exists()) {
                throw new Exception("El formulario de evaluación ya tiene respuestas");
            }

            $form->update([
                'title' => $request->title,
                'description' => $request->description,
                'score_max' => $request->score_max,
            ]);

            $questionKeys = array_column($request->questions, 'key');
            $form->questions()->whereNotIn('key', $questionKeys)->delete();
        } else {
            $form = TrainingForm::create([
                'training_content_id' => $content->id,
                'uuid' => $request->uuid,
                'title' => $request->title,
                'description' => $request->description,
                'score_max' => $request->score_max,
            ]);
        }

        foreach ($request->questions as $question) {
            $label = $question['label'];
            $options = $question['options'];

            $valid = array_filter($options, function ($option) {
                return isset($option['is_correct']) && $option['is_correct'] === true;
            });

            if (empty($valid)) {
                throw new Exception("En la pregunta $label al menos una opción debe ser la correcta");
            }

            $existingQuestion = $form->questions()->where('key', $question['key'])->first();

            if ($existingQuestion) {
                $existingQuestion->update([
                    'training_question_type_key' => $question['training_question_type_key'],
                    'label' => $question['label'],
                    'order_number' => $question['order_number'],
                    'score_max' => $question['score_max'],
                    'options' => $options,
                ]);
            } else {
                $form->questions()->create([
                    'training_question_type_key' => $question['training_question_type_key'],
                    'key' => $question['key'],
                    'label' => $question['label'],
                    'order_number' => $question['order_number'],
                    'score_max' => $question['score_max'],
                    'options' => $options,
                ]);
            }
        }
    }

    public static function delete(TrainingContent $content)
    {
        $form = $content->form;

        if ($form->responses()->exists()) {
            throw new Exception("El formulario de evaluación ya tiene respuestas");
        }

        $form->questions()->delete();
        $form->delete();

        $content->update([
            'has_evaluation_form' => false,
        ]);
    }

    public static function get(string $uuid, int $person_id)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::TRAINING_STUDENT;

        $form = TrainingForm::select()
            ->where('uuid', $uuid)
            ->with('questions')
            ->first();

        if (!$form) {
            throw new Exception("No se encontró el formulario de evaluación");
        }

        $content = $form->content;
        $training = $content->contentGroup->training;
        $training_id = $training->id;

        if (!$is_student) {
            if ($person_id === 0) {
                throw new Exception('El parámetro person_id es requerido');
            }

            $user = User::byKey('person_id', $person_id);
        }

        TrainingHelper::validateStudentAccess($user->person_id, $training_id, $is_student);

        TrainingContentRepository::manageStates($content);

        TrainingHelper::checkTrainingStatus($user, $training);

        if (!$content->is_visible) {
            throw new Exception("El contenido no está disponible");
        }

        $response = $form->responses()
            ->where('person_id', $user->person_id)
            ->first();

        $result = [
            'id' => $form->id,
            'training_content_id' => $form->training_content_id,
            'uuid' => $form->uuid,
            'title' => $form->title,
            'description' => $form->description,
            'score_max' => $form->score_max,
            'is_open' => $content->is_open,
        ];

        if ($response) {
            $result['is_pending'] = false;
            $result['score'] = $response->score;

            if (!$is_student) {
                $result['questions'] = $response->questions;
            }
        } else {
            $result['is_pending'] = true;
            $result['score'] = 0;

            if ($is_student) {
                $questions = $form->questions->toArray();
                foreach ($questions as &$question) {
                    foreach ($question['options'] as &$option) {
                        unset($option['is_correct']);
                    }
                }
                $result['questions'] = $questions;
            }
        }

        return $result;
    }

    public static function delivered(Request $request)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        TrainingEvaluationFormHelper::validateDeliveredRequest($request);

        $form = TrainingForm::select()
            ->where('uuid', $request->uuid)
            ->first();

        if (!$form) {
            throw new Exception("No se encontró el formulario de evaluación");
        }

        $content = $form->content;
        $training = $content->contentGroup->training;
        $training_id = $training->id;

        TrainingHelper::validateStudentAccess($user->person_id, $training_id);

        TrainingHelper::checkTrainingStatus($user, $training);

        $date = Carbon::now();
        $date_start = Carbon::parse($content->date_start);
        $date_limit = Carbon::parse($content->date_limit);

        if (!$content->is_open && ($date < $date_start || $date > $date_limit)) {
            throw new Exception('La tarea no admite respuestas');
        }

        $response = $form->responses()
            ->where('person_id', $user->person_id)
            ->first();

        if ($response) {
            $result = [
                'message' => "Ya has respondido este formulario de evaluación",
                'score' => $response->score,
                'total' => $form->score_max,
            ];

            return $result;
        }

        $score = 0;

        $questions = [];
        $questionsRequest = collect($request->questions)->keyBy('key');

        foreach ($form->questions as $question) {
            $questionRequest = $questionsRequest[$question->key] ?? null;
            if (!$questionRequest) {
                throw new Exception("No se encontró la pregunta: $question->label");
            }

            $optionTotalValid = 0;
            $optionTotal = count($question->options);

            $options = [];
            $optionsRequest = collect($questionRequest['options'])->keyBy('key');

            foreach ($question->options as $option) {
                $option = (object) $option;

                $optionRequest = $optionsRequest[$option->key] ?? null;
                if (!$optionRequest) {
                    throw new Exception("No se encontró la opción: $option->label");
                }

                $is_valid = $option->is_correct == $optionRequest['is_selected'];
                if ($is_valid) $optionTotalValid++;

                $options[] = [
                    'key' => $option->key,
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                    'is_selected' => $optionRequest['is_selected'],
                    'is_valid' => $is_valid,
                ];
            }

            $questionScore = round(($optionTotalValid / $optionTotal) * $question->score_max, 2);
            $score += $questionScore;

            $questions[] = [
                'id' => $question->id,
                'training_form_id' => $question->training_form_id,
                'training_question_type_key' => $question->training_question_type_key,
                'key' => $question->key,
                'label' => $question->label,
                'order_number' => $question->order_number,
                'score' => $questionScore,
                'score_max' => $question->score_max,
                'options' => $options,
            ];
        }

        $form->responses()->create([
            'person_id' => $user->person_id,
            'questions' => $questions,
            'score' => $score,
        ]);

        $answer = $content->answers()
            ->where('training_answer.person_id', $user->person_id)
            ->first();

        $answer->update([
            'status' => TrainingAnswerStatusEnum::EVALUATED,
            'score' => $score,
        ]);

        TrainingAverageRepository::calculateAverageDetail($training_id, $answer->person_id);

        return "¡Formulario de evaluación entregado y evaluado!";
    }
}
