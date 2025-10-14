<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            
        });

        $this->renderable(function (NotFoundHttpException $e) {
            $message = preg_replace(
                "/.*\[App\\\\Models\\\\(\w+)\].*/",
                "$1 not found",
                $e->getMessage() ?? "Not found!"
        );

            return errorResponse($message, 404);

            //  return response()->json([
            //     'success' => false,
            //     'message' => $message
            // ], 404);
        });
    }
}
