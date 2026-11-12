<?php

namespace Modules\Shared\Utils;

class Response
{
    public static function success($data = null, $message = 'Operación exitosa')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function error($message = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ]);
    }

    public static function unauthorized()
    {
        return response()->json([
            'error' => 'UnAuthenticated',
        ], 401);
    }

    public static function notFound($message)
    {
        return response()->json([
            'error' => 'NotFound',
            'message' => $message,
        ], 404);
    }

    public static function noSubscription($message)
    {
        return response()->json([
            'error' => 'SubscriptionEnded',
            'message' => $message,
        ], 410);
    }

    public static function forbidden($message = 'Módulo no habilitado o fuera de vigencia.')
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
        ], 403);
    }

    public static function file($binary, $type, $filename)
    {
        return response($binary)
            ->header('Content-Type', $type)
            ->header('Content-Disposition', 'attachment; filename=' . $filename)
            ->header('Access-Control-Expose-Headers', 'Content-Disposition');
    }
}
