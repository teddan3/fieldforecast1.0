<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CmsAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing CMS token.'], 401);
        }

        $user = User::query()
            ->where('cms_token', $token)
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'editor'])
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid CMS token.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
