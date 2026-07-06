<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ToastsValidationErrors;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\OpenStackUnreachableException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    use ToastsValidationErrors;

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
            'code' => ['required', 'string', 'max:50', Rule::unique('regions', 'code')->ignore($this->route('region'))],
            'host_url' => ['required', 'string', 'url', 'max:255'],
        ];
    }

    protected function passedValidation(): void
    {
        try {
            app(OpenStackClientInterface::class)->verifyIdentityEndpoint((string) $this->validated('host_url'));
        } catch (OpenStackUnreachableException) {
            $this->throwValidationToast('host_url', 'Unter dieser Host-URL ist kein OpenStack-Identity-Dienst erreichbar.');
        }
    }
}
