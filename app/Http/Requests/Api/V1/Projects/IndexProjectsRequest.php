<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Projects;

use App\Constants\Projects\ProjectMessages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class IndexProjectsRequest extends FormRequest
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MIN_PER_PAGE = 1;

    private const int MAX_PER_PAGE = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the page size is constrained. A page number past the last page
     * answers with an empty page and the correct total.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => [
                'sometimes',
                'bail',
                'integer',
                'between:'.self::MIN_PER_PAGE.','.self::MAX_PER_PAGE,
            ],
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
            'per_page.integer' => ProjectMessages::PER_PAGE_INVALID,
            'per_page.between' => ProjectMessages::PER_PAGE_INVALID,
        ];
    }

    /**
     * A query string is always text, so the page size is typed after validation
     * rather than before it — casting first would let `10.5` slip through as 10.
     */
    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }
}
