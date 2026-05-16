<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Http\Requests\Concerns\ToastsValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerActionRequest extends FormRequest
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
        $weekdayNames = array_map(fn (Weekday $w): string => $w->name, Weekday::cases());

        return [
            'server_id' => ['required', 'integer', 'exists:servers,id'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', Rule::enum(ActionType::class)],
            'actions.*.time' => ['required', 'date_format:H:i'],
            'actions.*.days' => ['required', 'array', 'min:1'],
            'actions.*.days.*' => ['required', 'string', Rule::in($weekdayNames)],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function groupedAttributes(): array
    {
        $serverId = (int) $this->validated('server_id');

        return array_map(
            fn (array $action): array => [
                'server_id' => $serverId,
                'type' => $action['type'],
                'time' => $action['time'],
                'weekday' => Weekday::combine(
                    array_map(fn (string $name): Weekday => Weekday::{$name}, $action['days']),
                ),
            ],
            $this->validated('actions'),
        );
    }
}
