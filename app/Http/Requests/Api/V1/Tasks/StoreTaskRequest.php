<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Tasks;

use App\Constants\Tasks\TaskMessages;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreTaskRequest extends FormRequest
{
    private const int TITLE_MIN = 3;

    private const int TITLE_MAX = 255;

    private const int DESCRIPTION_MAX = 2000;

    private const string DATE_FORMAT = 'Y-m-d';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The project rule is scoped to the caller and excludes deleted projects, so
     * naming another account's project is answered exactly as if that project did
     * not exist. `Rule::exists` runs a raw query, so the soft-delete
     * condition has to be spelled out.
     *
     * There is deliberately no rule holding the due date at or after today: a date
     * in the past is a legitimate record of work already late.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'min:'.self::TITLE_MIN, 'max:'.self::TITLE_MAX],
            'description' => ['nullable', 'string', 'max:'.self::DESCRIPTION_MAX],
            'priority' => ['nullable', new Enum(TaskPriority::class)],
            'status' => ['nullable', new Enum(TaskStatus::class)],
            'due_date' => ['required', 'date_format:'.self::DATE_FORMAT],
        ];
    }

    /**
     * Every way of naming an unreachable project answers with the same sentence,
     * so a missing id, a non-numeric id and another account's id are
     * indistinguishable from one another.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => TaskMessages::PROJECT_INVALID,
            'project_id.integer' => TaskMessages::PROJECT_INVALID,
            'project_id.exists' => TaskMessages::PROJECT_INVALID,
            'title.required' => TaskMessages::TITLE_REQUIRED,
            'title.min' => TaskMessages::TITLE_SHORT,
            'title.max' => TaskMessages::TITLE_LONG,
            'description.max' => TaskMessages::DESCRIPTION_LONG,
            'priority.enum' => TaskPriority::invalidMessage(),
            'status.enum' => TaskStatus::invalidMessage(),
            'due_date.required' => TaskMessages::DUE_DATE_REQUIRED,
            'due_date.date_format' => TaskMessages::DUE_DATE_INVALID,
        ];
    }

    /**
     * Trim the title so a whitespace-only value is rejected as blank rather than
     * stored as blank, and lower-case the two choices so that `High` and
     * `high` name the same value.
     */
    protected function prepareForValidation(): void
    {
        $normalised = [];

        $title = $this->input('title');

        if (is_string($title)) {
            $normalised['title'] = Str::trim($title);
        }

        foreach (['priority', 'status'] as $choice) {
            $value = $this->input($choice);

            if (is_string($value)) {
                $normalised[$choice] = Str::lower($value);
            }
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
