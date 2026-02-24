<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserRh;
use App\Models\RememberToken;
use Illuminate\Support\Facades\Hash;

/**
 * Auth User RH Middleware
 * Mirror dari: legacy/auth/auth_guard.php
 *
 * Cek session auth_user_rh_id, jika tidak ada cek remember token
 */
class AuthUserRh
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Jika sudah ada session user_rh, lanjutkan
        if (session()->has('user_rh')) {
            return $next($request);
        }

        // Cek remember login cookie
        if ($request->hasCookie('remember_login')) {
            $cookieValue = $request->cookie('remember_login');

            // Parse cookie: userId:token
            if (strpos($cookieValue, ':') !== false) {
                [$userId, $token] = explode(':', $cookieValue, 2);

                // Cari token yang masih valid
                $tokens = RememberToken::where('user_id', $userId)
                    ->where('expired_at', '>', now())
                    ->get();

                foreach ($tokens as $row) {
                    if (Hash::check($token, $row->token_hash)) {
                        // Token valid, restore session
                        $user = UserRh::find($userId);

                        if ($user) {
                            session([
                                'user_rh' => [
                                    'id'       => $user->id,
                                    'name'     => $user->full_name,
                                    'role'     => $user->role,
                                    'position' => $user->position
                                ]
                            ]);

                            return $next($request);
                        }
                    }
                }
            }
        }

        // Tidak ada session valid dan tidak ada remember token valid
        // Hapus cookie dan redirect ke login
        return redirect('/login')
            ->withCookie(cookie()->forget('remember_login'));
    }
}
