<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Region;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class StoreProjectRequest extends FormRequest
{
    private string $resolvedOpenStackProjectId;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'app_credential_id' => ['required', 'string', 'max:255'],
            'app_credential_secret' => ['required', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        try {
            $region = Region::findOrFail((int) $this->validated('region_id'));

            $result = app(OpenStackClientInterface::class)->authenticate(
                $region->host_url,
                (string) $this->validated('app_credential_id'),
                (string) $this->validated('app_credential_secret'),
            );

            if (Project::where('open_stack_project_id', $result->projectId)->exists()) {
                throw ValidationException::withMessages([
                    'app_credential_id' => 'Ein Projekt mit dieser OpenStack-Projekt-ID existiert bereits.',
                ]);
            }

            $this->resolvedOpenStackProjectId = $result->projectId;
        } catch (InvalidOpenStackCredentialsException) {
            $this->throwHtmxOrFlash('Ungültige OpenStack-Zugangsdaten.', 'app_credential_secret');
        } catch (ValidationException $e) {
            $this->throwHtmxOrFlash($e->validator->errors()->first(), 'app_credential_id');
        }
    }

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
        session()->flash('store_project_error', true);
        parent::failedValidation($validator);
    }

    private function throwHtmxOrFlash(string $message, string $field): never
    {
        if ($this->header('HX-Request')) {
            throw new HttpResponseException(
                response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => $message, 'type' => 'danger']])
                )
            );
        }

        session()->flash('store_project_error', true);
        throw ValidationException::withMessages([$field => $message]);
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        return [
            ...$this->validated(),
            'open_stack_project_id' => $this->resolvedOpenStackProjectId,
        ];
    }
}
