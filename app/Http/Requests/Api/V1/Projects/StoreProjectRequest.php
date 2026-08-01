<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

use App\Constants\Projects\ProjectMessages;
use App\Enums\Projects\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

final class StoreProjectRequest extends FormRequest
{
    private const int NAME_MIN = 3;

    private const int NAME_MAX = 255;

    private const int DESCRIPTION_MAX = 2000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * An owner is never accepted from the body, so `user_id` has no rule and is
     * dropped from the validated data (BR-1, EC-7).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:'.self::NAME_MIN, 'max:'.self::NAME_MAX],
            'description' => ['nullable', 'string', 'max:'.self::DESCRIPTION_MAX],
            'status' => ['nullable', new Enum(ProjectStatus::class)],
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
