<?php

namespace App\Helpers;

class JsonResponseHelper
{
    /**
     * Convert HTTP status code to status string
     */
    public static function getStatus(int $status): string
    {
        if ($status >= 200 && $status < 300) return 'success';
        if ($status >= 400 && $status < 500) return 'fail';
        if ($status >= 500) return 'error';
        return 'unknown';
    }

    /**
     * Standard JSON response
     */
    public static function standardResponse(
        $status,
        $data = null,
        $message = null,
        $messages = null,
        $extra = []
    ) {
        return response()->json(
            array_merge([
                'status' => self::getStatus($status),
                'message' => $message,
                'data' => $data,
                'messages' => $messages
            ], $extra),
            $status
        );
    }
}
