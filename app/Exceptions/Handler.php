<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    // App\Exceptions\Handler.php


    public function render($request, Throwable $exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'API endpoint not found.'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle unauthenticated exceptions.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // 💡 CRITICAL FIX: If the request is expecting JSON (typical for API calls),
        // return a 401 JSON response. This stops the framework from trying to redirect.
        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
                'code' => 401
            ], 401);
        }

        // Fallback for standard web-based requests (if any)
        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
