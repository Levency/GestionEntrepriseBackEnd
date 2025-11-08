<?php


if (!function_exists('successResponse')) {
    function successResponse($data = null, $status = 200, $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $status,
            'data' => $data,
        ], $status);
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse($message = 'Error', $status = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => $status,
            'errors' => $errors,
        ], $status);
    }
}