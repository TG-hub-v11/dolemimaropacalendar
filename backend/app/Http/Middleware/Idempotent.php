<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mobile sync retries send the same `Idempotency-Key` (a UUID operation id). The first response is
 * stored and replayed for later retries, so a retried POST never creates a duplicate.
 */
class Idempotent
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        if (! $key || ! $request->user() || ! preg_match('/^[0-9a-fA-F-]{36}$/', $key)) {
            return $next($request);
        }
        $userId = $request->user()->id;
        $row = DB::table('idempotency_keys')->where(['user_id' => $userId, 'operation_id' => $key])->first();
        if ($row) {
            return response()->json(json_decode($row->response_body, true), $row->response_status, ['Idempotent-Replay' => 'true']);
        }
        $response = $next($request);
        if ($response->getStatusCode() < 500) {
            DB::table('idempotency_keys')->insertOrIgnore([
                'user_id' => $userId, 'operation_id' => $key, 'method' => $request->method(),
                'path' => $request->path(), 'response_status' => $response->getStatusCode(),
                'response_body' => $response->getContent(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $response;
    }
}
