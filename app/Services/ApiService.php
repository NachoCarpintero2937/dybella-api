<?php

namespace App\Services;

class ApiService
{
    public function sendResponse($data = [], $message = '', $statusCode = 200, $status = false)
    {
        $response = [
            'status' => $status,
            'message' => $message ? $message : 'Ok',
            'data' => $data,
        ];

        return response()->json($response, $statusCode);
    }
}
