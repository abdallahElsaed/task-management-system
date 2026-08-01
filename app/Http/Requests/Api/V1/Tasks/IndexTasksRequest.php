<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Tasks;

use App\Constants\Tasks\TaskMessages;
use App\DTOs\Tasks\TaskFilters;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class IndexTasksRequest extends FormRequest
{
    private const int DEFAULT_PER_PAGE = 15;

    private const int MIN_PER_PAGE = 1;

    private const int MAX_PER_PAGE = 100;

    private const int SEARCH_MAX = 255;

    /**
     * The three parameters whose value is a choice rather than free text. An empty
     * one is stripped before validation, so it reads as an absent filter.
     *
     * @var array<int, string>
     */
    private const array CHOICE_FILTERS = ['status', 'priority', 'project_id'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A filter value outside its allowed set rejects the whole request rather than
     * being ignored, so no page is returned at all (BR-18, EC-5). The Enum rules
     * are what deliver that, and they cannot fall out of step with the cases.
     *
     * The project filter is scoped to the caller, so another account's project id
     * is rejected the same way an unknown one is (AC-5.5).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', new Enum(TaskStatus::class)],
            'priority' => ['sometimes', 'nullable', new Enum(TaskPriority::class)],
            'project_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'search' => ['sometimes', 'nullable', 'string', 'max:'.self::SEARCH_MAX],
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
            'status.enum' => TaskStatus::invalidMessage(),
            'priority.enum' => TaskPriority::invalidMessage(),
            'project_id.integer' => TaskMessages::PROJECT_INVALID,
            'project_id.exists' => TaskMessages::PROJECT_INVALID,
            'search.max' => TaskMessages::SEARCH_TOO_LONG,
            'per_page.integer' => TaskMessages::PER_PAGE_INVALID,
            'per_page.between' => TaskMessages::PER_PAGE_INVALID,
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

    /**
     * The four narrowing terms, ready for the repository to combine with AND (BR-17).
     *
     * A search term that trimmed away becomes null rather than an empty string,
     * which is what makes it an absent filter and returns the unfiltered page with
     * the unfiltered message (EC-10, AC-6.5).
     */
    public function filters(): TaskFilters
    {
        $status = $this->validated('status');
        $priority = $this->validated('priority');
        $projectId = $this->validated('project_id');
        $search = $this->validated('search');

        return new TaskFilters(
            status: is_string($status) ? TaskStatus::from($status) : null,
            priority: is_string($priority) ? TaskPriority::from($priority) : null,
            projectId: $projectId !== null ? (int) $projectId : null,
            search: is_string($search) && $search !== '' ? $search : null,
        );
    }

    /**
     * Trim the search term (BR-19, EC-7) and lower-case the two choices so that
     * `Todo` and `todo` name the same value.
     *
     * BR-18 governs filter *values*: a parameter sent with nothing in it carries no
     * value to reject, so it becomes null and reads as no filter rather than as an
     * unrecognised one. The `nullable` rule then skips the rest of its chain.
     */
    protected function prepareForValidation(): void
    {
        $normalised = [];

        $search = $this->input('search');

        if (is_string($search)) {
            $normalised['search'] = Str::trim($search);
        }

        foreach (self::CHOICE_FILTERS as $filter) {
            $value = $this->input($filter);

            if (! is_string($value)) {
                continue;
            }

            $value = Str::lower(Str::trim($value));

            $normalised[$filter] = $value === '' ? null : $value;
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
