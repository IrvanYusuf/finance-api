<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, $message = 'Success get data', $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $status);
    }

    public static function error($message = 'Terjadi kesalahan', $errors = null, $status = 400)
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $status);
    }
}
