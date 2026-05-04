<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
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
            'name'                  => ['nullable', 'string', 'max:255'],
            'app_credential_id'     => ['nullable', 'string', 'max:255'],
            'app_credential_secret' => ['nullable', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        /** @var Project $project */
        $project = $this->route('project');
        session()->flash('edit_project_id', $project->id);

        try {
            $this->verifyCredentials($project);
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    private function verifyCredentials(Project $project): void
    {
        $submittedId     = $this->validated('app_credential_id');
        $submittedSecret = $this->validated('app_credential_secret');

        // Kein Credential angegeben → nur Name wird gespeichert, keine Auth nötig
        if (! $submittedId && ! $submittedSecret) {
            return;
        }

        // Fehlende Seite aus der DB ergänzen
        $credentialId     = $submittedId     ?: $project->app_credential_id;
        $credentialSecret = $submittedSecret ?: $project->app_credential_secret;

        // Credentials unverändert → keine Auth nötig
        if ($credentialId === $project->app_credential_id
            && $credentialSecret === $project->app_credential_secret) {
            return;
        }

        try {
            $result = app(OpenStackClientInterface::class)->authenticate(
                $credentialId,
                $credentialSecret,
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
     * Felder für das Update zusammenstellen.
     * Wenn nur ein Credential angegeben wurde, wird das andere aus der DB ergänzt,
     * damit immer ein konsistentes Paar gespeichert wird.
     *
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        $attrs = array_filter(
            $this->validated(),
            fn ($value) => ! is_null($value) && $value !== '',
        );

        // Wenn nur eines der Credentials angegeben → das andere aus DB ergänzen
        $hasId     = ! empty($attrs['app_credential_id']);
        $hasSecret = ! empty($attrs['app_credential_secret']);

        if ($hasId && ! $hasSecret) {
            $attrs['app_credential_secret'] = $project->app_credential_secret;
        } elseif ($hasSecret && ! $hasId) {
            $attrs['app_credential_id'] = $project->app_credential_id;
        }

        return $attrs;
    }

    protected function failedValidation(Validator $validator): void
    {
        /** @var Project $project */
        $project = $this->route('project');
        session()->flash('edit_project_id', $project->id);

        parent::failedValidation($validator);
    }

}
