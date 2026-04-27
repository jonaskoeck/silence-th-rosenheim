<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\OpenStackClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'app_credential_id' => ['required', 'string', 'max:255'],
            'app_credential_secret' => ['required', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        try {
            $result = app(OpenStackClient::class)->authenticate(
                (string) $this->validated('app_credential_id'),
                (string) $this->validated('app_credential_secret'),
            );
        } catch (InvalidOpenStackCredentialsException) {
            throw ValidationException::withMessages([
                'app_credential_secret' => 'Ungültige OpenStack-Zugangsdaten.',
            ]);
        }

        $this->resolvedOpenStackProjectId = $result->projectId;
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
