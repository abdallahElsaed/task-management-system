<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

use App\Constants\Projects\ProjectMessages;
use App\Enums\Projects\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class UpdateProjectRequest extends FormRequest
{
    private const int NAME_MIN = 3;

    private const int NAME_MAX = 255;

    private const int DESCRIPTION_MAX = 2000;

    /**
     * @var array<int, string>
     */
    private const array UPDATABLE_FIELDS = ['name', 'description', 'status'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every field is optional, but a field that is present must still be valid,
     * so a name of "   " is rejected rather than blanking the stored name (EC-4).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:'.self::NAME_MIN, 'max:'.self::NAME_MAX],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.self::DESCRIPTION_MAX],
            'status' => ['sometimes', 'required', new Enum(ProjectStatus::class)],
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
            'name.required' => ProjectMessages::NAME_REQUIRED,
            'name.min' => ProjectMessages::NAME_SHORT,
            'name.max' => ProjectMessages::NAME_LONG,
            'description.max' => ProjectMessages::DESCRIPTION_LONG,
            'status.enum' => ProjectStatus::invalidMessage(),
        ];
    }

    /**
     * An update naming no editable field is a mistake rather than a no-op, so it
     * is rejected and the project is left untouched (EC-13). The error is keyed
     * on the resource, not on a field the caller never sent.
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

                $validator->errors()->add('project', ProjectMessages::NO_UPDATABLE_FIELDS);
            },
        ];
    }

    /**
     * Trim the name so a whitespace-only value is rejected as blank rather than
     * stored as blank (BR-3, EC-4), and lower-case the status so that `Active`
     * and `active` name the same choice (EC-5).
     */
    protected function prepareForValidation(): void
    {
        $normalised = [];

        $name = $this->input('name');

        if (is_string($name)) {
            $normalised['name'] = Str::trim($name);
        }

        $status = $this->input('status');

        if (is_string($status)) {
            $normalised['status'] = Str::lower($status);
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
