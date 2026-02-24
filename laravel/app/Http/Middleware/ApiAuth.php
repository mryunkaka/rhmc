<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;

class ApiAuth
{
    /**
     * Mirror legacy/api/middleware/auth.php
     */
    public function handle(Request $request, Closure $next)
    {
        $authorization = (string)($request->header('Authorization') ?? '');
        if (trim($authorization) === '') {
            return response()->json(['error' => 'No Authorization'], 401);
        }

        $clientId = (string)($request->header('X-Client-Id') ?? '');
        if (trim($clientId) === '') {
            return response()->json(['error' => 'No Client ID'], 401);
        }

        $token = trim(str_ireplace('Bearer', '', $authorization));
        $clientId = trim($clientId);

        $authorized = ApiToken::query()
            ->whereRaw('TRIM(token) = ?', [$token])
            ->whereRaw('TRIM(client_id) = ?', [$clientId])
            ->where('is_active', 1)
            ->exists();

        if (!$authorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}

