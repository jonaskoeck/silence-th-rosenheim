<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\OpenStackClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateProjectRequest extends FormRequest
{
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
        /** @var Project $project */
        $project = $this->route('project');

        $credentialsChanged = $this->credentialsChanged($project);

        if (! $credentialsChanged) {
            return;
        }

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

        if ($result->projectId !== $project->open_stack_project_id) {
            throw ValidationException::withMessages([
                'app_credential_id' => 'Diese Zugangsdaten gehören zu einem anderen OpenStack-Projekt.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        return $this->validated();
    }

    private function credentialsChanged(Project $project): bool
    {
        return $this->validated('app_credential_id') !== $project->app_credential_id
            || $this->validated('app_credential_secret') !== $project->app_credential_secret;
    }
}
