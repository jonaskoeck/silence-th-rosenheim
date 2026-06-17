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

class UpdateServerActionsRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:120'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', Rule::enum(ActionType::class)],
            'actions.*.time' => $this->scheduleTimeRules(),
            'actions.*.days' => ['required', 'array', 'min:1'],
            'actions.*.days.*' => ['required', 'string', Rule::in($weekdayNames)],
            'confirmed_production' => ['nullable', 'string'],
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
                $server = $this->route('server');

                if (! $server instanceof Server || $server->label !== ServerLabel::PRODUCTION) {
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
        $server = $this->route('server');
        $serverId = $server instanceof Server ? $server->id : 0;

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
