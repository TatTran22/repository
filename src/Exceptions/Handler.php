<?php

namespace TatTran\Repository\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use TatTran\Repository\Cache\FlushCache;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        AuthenticationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        try {
            FlushCache::request($request);
        } catch (\Throwable $th) {
            // Handle any exceptions that occur during cache flush gracefully
        }

        if (env('APP_DEBUG') && !$request->ajax()) {
            return parent::render($request, $e);
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Internal Server Error';

        if ($e instanceof HttpResponseException) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response = $e->getResponse();
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $status = Response::HTTP_METHOD_NOT_ALLOWED;
            $message = 'Method Not Allowed';
        } elseif ($e instanceof NotFoundHttpException) {
            $status = Response::HTTP_NOT_FOUND;
            $message = 'Not Found';
        } elseif ($e instanceof AuthorizationException) {
            $status = Response::HTTP_FORBIDDEN;
            $message = 'Forbidden';
        } elseif ($e instanceof ValidationException && $e->getResponse()) {
            $status = Response::HTTP_BAD_REQUEST;
            $message = 'Validation Failed';
            $response = $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            $status = Response::HTTP_UNAUTHORIZED;
            $message = 'Unauthorized';
            $response = $e->getMessage();
        }

        return response()->json([
            'success' => false,
            'status' => $status,
            'code' => $status,
            'message' => $message
        ], $status, [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ]);
    }
}
