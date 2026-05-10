<?php

namespace App\Http\Middleware;

use App\Support\ActingAdmin;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): ResponseAlias
    {
        $header = $request->header('Authorization', '');
        if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return response()->json(['message' => 'Missing bearer token'], 401);
        }

        $secret = config('services.jwt.secret');
        if (!$secret) {
            return response()->json(['message' => 'JWT not configured'], 500);
        }

        JWT::$leeway = (int) config('services.jwt.leeway', 10);

        try {
            $payload = JWT::decode($m[1], new Key($secret, 'HS256'));
        } catch (ExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json(['message' => 'Invalid token signature'], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $expectedAud = config('services.jwt.audience');
        $expectedIss = config('services.jwt.issuer');

        if ($expectedAud && (($payload->aud ?? null) !== $expectedAud)) {
            return response()->json(['message' => 'Invalid audience'], 403);
        }
        if ($expectedIss && (($payload->iss ?? null) !== $expectedIss)) {
            return response()->json(['message' => 'Invalid issuer'], 403);
        }
        if (!isset($payload->sub)) {
            return response()->json(['message' => 'Token missing subject'], 401);
        }

        $admin = new ActingAdmin(
            id: (int) $payload->sub,
            name: $payload->name ?? null,
            email: $payload->email ?? null,
        );

        app()->instance(ActingAdmin::class, $admin);
        $request->attributes->set('acting_admin', $admin);

        return $next($request);
    }
}
