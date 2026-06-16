<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActionType;
use App\Enums\ServerLabel;
use App\Enums\Weekday;
use App\Http\Requests\Concerns\ToastsValidationErrors;
use App\Http\Requests\Concerns\ValidatesScheduleTimeConflicts;
use App\Models\Server;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServerActionRequest extends FormRequest
{
    use ToastsValidationErrors;
    use ValidatesScheduleTimeConflicts;

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
            'name' => ['nullable', 'string', 'max:120'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', Rule::enum(ActionType::class)],
            'actions.*.time' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):[0-5][05]$/'],
            'actions.*.days' => ['required', 'array', 'min:1'],
            'actions.*.days.*' => ['required', 'string', Rule::in($weekdayNames)],
            'confirmed_production' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'actions.*.time.regex' => 'Uhrzeiten sind nur in 5-Minuten-Schritten möglich.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->addScheduleTimeConflictErrors($validator);
            },
            function (Validator $validator): void {
                $server = Server::find((int) $this->input('server_id'));

                if ($server === null || $server->label !== ServerLabel::PRODUCTION) {
                    return;
                }

                if ($this->input('confirmed_production') !== '1') {
                    $validator->errors()->add(
                        'confirmed_production',
                        'Sicherheitsabfrage für Produktivserver muss bestätigt werden.',
                    );
                }
            },
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
