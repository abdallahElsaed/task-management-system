<?php

use App\Exceptions\InvalidCredentialsException;
use App\Http\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This API has no login page, so an unauthenticated caller is never redirected.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $e instanceof HttpResponseException => null,
                $e instanceof InvalidCredentialsException => ApiResponse::unauthorized($e->getMessage()),
                $e instanceof AuthenticationException => ApiResponse::unauthorized(),
                $e instanceof ValidationException => ApiResponse::validationError($e->getMessage(), $e->errors()),
                $e instanceof NotFoundHttpException => ApiResponse::notFound('Endpoint not found.'),
                $e instanceof MethodNotAllowedHttpException => ApiResponse::methodNotAllowed(),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    $e->getMessage() !== '' ? $e->getMessage() : 'Something went wrong. Please try again.',
                    $e->getStatusCode(),
                ),
                default => config('app.debug') ? null : ApiResponse::serverError(),
            };
        });
    })->create();
