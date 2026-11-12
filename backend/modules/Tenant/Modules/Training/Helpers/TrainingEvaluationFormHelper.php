<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingEvaluationFormHelper
{
    public static function validateRequest($request, $isUpdate)
    {
        $validation = $isUpdate ? "exists" : "unique";

        $validator = Validator::make($request, [
            "uuid"                                      => "required|string|$validation:training_form,uuid",
            "title"                                     => "required|string",
            "description"                               => "nullable|string",
            "score_max"                                 => "required|numeric|between:0,20",
            "questions"                                 => "required|array|min:1",
            "questions.*.key"                           => "required|string",
            "questions.*.label"                         => "required|string",
            "questions.*.training_question_type_key"    => "required|string|exists:training_question_type,key",
            "questions.*.order_number"                  => "required|integer|min:1",
            "questions.*.score_max"                     => "required|numeric|between:0,20",
            "questions.*.options"                       => "required|array|min:2",
            "questions.*.options.*.key"                 => "required|string",
            "questions.*.options.*.label"               => "required|string",
            "questions.*.options.*.is_correct"          => "required|boolean",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDeliveredRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "uuid"                                      => "required|string|exists:training_form,uuid",
            "questions"                                 => "required|array|min:1",
            "questions.*.key"                           => "required|string|exists:training_question,key",
            "questions.*.label"                         => "required|string",
            "questions.*.training_question_type_key"    => "required|string|exists:training_question_type,key",
            "questions.*.order_number"                  => "required|integer|min:1",
            "questions.*.score_max"                     => "required|numeric|between:0,20",
            "questions.*.options"                       => "required|array|min:2",
            "questions.*.options.*.key"                 => "required|string",
            "questions.*.options.*.label"               => "required|string",
            "questions.*.options.*.is_selected"         => "required|boolean",
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('questions') as $question) {
                $selectedOptions = array_filter($question['options'], function ($option) {
                    return $option['is_selected'];
                });

                if (count($selectedOptions) < 1) {
                    $validator->errors()->add('questions', 'Cada pregunta debe tener al menos una opción seleccionada.');
                    break;
                }
            }
        });

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
