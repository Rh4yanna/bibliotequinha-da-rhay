<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token && strlen($token) <= 256, 401, 'Token necessário.');
        $record = DB::table('api_tokens')->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())->first();
        $user = $record ? User::find($record->user_id) : null;
        abort_unless($user, 401, 'Token inválido ou expirado.');
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
