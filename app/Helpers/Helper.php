<?php

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

function successResponse($message,  $data = [], $statusCode = 200)
{
    $messages = [
        'success' => true,
        'message' => $message,
        'data' => $data,
        'code' => $statusCode
    ];
    if ($data instanceof AnonymousResourceCollection) {
        unset($messages['data']);
        return $data->additional($messages);
    }
    return response()->json($messages, $statusCode);
}

function errorResponse($message, $statusCode)
{
    return response()->json([
        'success' => false,
        'error' => $message,
        'code' => $statusCode
    ], $statusCode);
}