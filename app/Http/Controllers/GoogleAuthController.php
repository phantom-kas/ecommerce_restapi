<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return JsonResponseHelper::standardResponse(200, ['url' => $url], 'Google redirect URL');
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();


            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(str()->random(16)),
                    'image' => $googleUser->getAvatar(),
                    'role' => 'user'
                ]);
            } else {

                $user->update([
                    'image' => $googleUser->getAvatar(),
                ]);
            }

            // $accessToken = $user->createToken('access_token');
            $refreshToken = $user->createToken('refresh_token','google auth');



            return redirect(env('FRONTEND_ORIGIN') . '/auth/callback?tkn='.$refreshToken)
                ->withCookie(JsonResponseHelper::makeRefreshCookie($refreshToken));
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Failed to login with Google.');
        }
    }
}
