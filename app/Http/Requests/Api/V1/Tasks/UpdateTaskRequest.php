<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Tasks;

use App\Constants\Tasks\TaskMessages;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class UpdateTaskRequest extends FormRequest
{
    private const int TITLE_MIN = 3;

    private const int TITLE_MAX = 255;

    private const int DESCRIPTION_MAX = 2000;

    private const string DATE_FORMAT = 'Y-m-d';

    /**
     * @var array<int, string>
     */
    private const array UPDATABLE_FIELDS = ['title', 'description', 'priority', 'status', 'due_date'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional, but a field that is present must still be valid, so
     * a title of "   " is rejected rather than blanking the stored title (AC-2.6).
     *
     * `project_id` carries no rule at all, so it is dropped from the validated data
     * and an update naming another project leaves the task where it is (BR-2, EC-15).
     *
     * A status moves between any two values in either direction — nothing here
     * restricts which value may follow which (BR-9).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:'.self::TITLE_MIN, 'max:'.self::TITLE_MAX],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.self::DESCRIPTION_MAX],
            'priority' => ['sometimes', 'required', new Enum(TaskPriority::class)],
            'status' => ['sometimes', 'required', new Enum(TaskStatus::class)],
            'due_date' => ['sometimes', 'required', 'date_format:'.self::DATE_FORMAT],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
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
     * An update naming no editable field is a mistake rather than a no-op, so it is
     * rejected and the task is left untouched (EC-19). The error is keyed on the
     * resource, not on a field the caller never sent.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hasAny(self::UPDATABLE_FIELDS)) {
                    return;
                }

                $validator->errors()->add('task', TaskMessages::NO_UPDATABLE_FIELDS);
            },
        ];
    }

    /**
     * Trim the title so a whitespace-only value is rejected as blank rather than
     * stored as blank (BR-5), and lower-case the two choices so that `High` and
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
