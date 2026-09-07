<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Format respons sukses standar.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success(mixed $data = null, string $message = 'Data retrieved successfully', int $code = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($payload, $code);
    }

    /**
     * Format respons error standar.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  mixed  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = 'An error occurred', int $code = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
