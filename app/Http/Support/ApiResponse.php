<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds every JSON body the API returns.
 *
 * Success bodies are `{success: true, message?, data, meta?}` and failures are
 * `{success: false, message, errors?}`, where `errors` is keyed by field name.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = Response::HTTP_OK
    ): JsonResponse {
        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return self::success($data, $message, $meta, Response::HTTP_CREATED);
    }

    /**
     * Wrap one page of a resource collection, so every list endpoint carries the
     * same pagination meta whatever it returns.
     *
     * A resource collection resolves to its items alone, so the page numbers are
     * read from the paginator behind it rather than from the collection.
     */
    public static function paginated(ResourceCollection $collection, ?string $message = null): JsonResponse
    {
        /** @var LengthAwarePaginator<int, mixed> $page */
        $page = $collection->resource;

        return self::success(
            data: $collection->resolve(),
            message: $message,
            meta: [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        );
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function validationError(string $message, array $errors): JsonResponse
    {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    public static function methodNotAllowed(
        string $message = 'This method is not allowed for this endpoint.'
    ): JsonResponse {
        return self::error($message, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public static function tooManyRequests(string $message = 'Too many requests.'): JsonResponse
    {
        return self::error($message, Response::HTTP_TOO_MANY_REQUESTS);
    }

    public static function serverError(
        string $message = 'Something went wrong. Please try again.'
    ): JsonResponse {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
