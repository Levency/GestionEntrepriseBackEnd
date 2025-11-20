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

if (!function_exists('generateEmployeeCode')) {
    function generateEmployeeCode($firstName, $lastName)
    {
        // Prendre les 2 premières lettres du prénom et 2 du nom
        $namePrefix = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

        // Générer 4 chiffres aléatoires uniques
        $randomNumbers = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Combiner pour obtenir 6 caractères : 2 lettres + 4 chiffres
        $code = $namePrefix . $randomNumbers;

        return $code;
    }
}