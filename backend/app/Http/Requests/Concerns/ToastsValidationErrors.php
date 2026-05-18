<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ToastsValidationErrors
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->header('HX-Request')) {
            throw new HttpResponseException(
                response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => $validator->errors()->first(), 'type' => 'danger']])
                )
            );
        }

        parent::failedValidation($validator);
    }
}
