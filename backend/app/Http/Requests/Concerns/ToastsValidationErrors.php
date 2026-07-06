<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

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

    /**
     * Fail validation from within passedValidation(), surfacing the message as
     * an htmx toast or a regular validation error depending on the request.
     */
    protected function throwValidationToast(string $field, string $message): never
    {
        if ($this->header('HX-Request')) {
            throw new HttpResponseException(
                response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => $message, 'type' => 'danger']])
                )
            );
        }

        throw ValidationException::withMessages([$field => $message]);
    }
}
