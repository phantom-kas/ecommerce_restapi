<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\MailHelper;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\JsonResponseHelper;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function register(Request $request)
    {
        try {
            // dd('Error');
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'in:user,admin,super_admin',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            //   return JsonResponseHelper::standardResponse(
            //     200,      // status code
            //     [1, 2, 3, 4, 5],   // data
            //     'Profile retrieved successfully' // message
            // );
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $validated['password'],
                'role'     => $validated['role'] ?? 'user',
            ]);
            // Create Sanctum token

            $verifyLink = env('FRONTEND_ORIGIN') . '/verify-email';
            $htmlBody = view('emails.verify', [
                'name' => $user->name,
                'verifyLink' => $verifyLink
            ])->render();
            MailHelper::sendVerificationEmail($user->email, "Verify Your Email", $htmlBody);
            return JsonResponseHelper::standardResponse(
                200,
                $user,
                'Profile retrieved successfully'
            );


            // your code
        } catch (\Throwable $e) {
            return JsonResponseHelper::standardResponse(
                500,
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ],
                'Internal Error'
            );
            // return response()->json(, 500);
        }
    }




    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return JsonResponseHelper::standardResponse(
                401,
                null,
                'Invalid credentials'
            );
        }
        if (isset($user->password)) {
            unset($user->password);
        }
        $accessToken = $user->createToken('access_token');
        $refreshToken = $user->createToken('refresh_token','login');


        return JsonResponseHelper::standardResponse(
            200,
            [
                'user' => $user,
                'access_token' => $accessToken,
                'refresh_token' =>  null,
            ],
            'Login successful'
        )->withCookie(JsonResponseHelper::makeRefreshCookie($refreshToken));
    }

    public function me()
    {
        $id = request()->user->id;
        return     JsonResponseHelper::standardResponse(200, DB::select("SELECT id , name , image ,email , role from users where id = ?", [$id]), 'Success');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token'); // or from header/body
        if (!$refreshToken) {
            return JsonResponseHelper::standardResponse(401, null, 'Refresh token missing');
        }

        $hashed = hash('sha256', $refreshToken);
        $record = DB::select('select user_id ,id,token from refresh_tokens where  token = ? limit 1', [ $hashed]);

        if (empty($record)) {
            return JsonResponseHelper::standardResponse(
                401,
                null,
                'Invalid or expired refresh token '
            );
        }
        $record = $record[0];
         if ($record['expires_at'] > now()) {
            return JsonResponseHelper::standardResponse(
                401,
                null,
                'Token expired'
            );
        }
        if ($record['is_refresh'] == 1) {
            return JsonResponseHelper::standardResponse(
                401,
                null,
                'Token revoked'
            );
        }
        $user = User::find($record->user_id);
        $accessToken = $user->createToken('access_token');
        $newRefreshToken = $user->createToken('refresh_token', 'refresh');


        return JsonResponseHelper::standardResponse(
            200,
            [
                'access_token'  => $accessToken,
                'refresh_token' => null,
            ],
            'new rtkn'
        )->withCookie(JsonResponseHelper::makeRefreshCookie($refreshToken));
    }




    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);


        $token = Str::random(64);

        // Store token in password_resets table
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Send reset email
        $resetLink = env('FRONTEND_ORIGIN') . "/reset-password?token=$token&email=" . $request->email;

        Mail::send('emails.reset-password', ['resetLink' => $resetLink], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset your password');
        });

        return JsonResponseHelper::standardResponse(
            200,
            null,
            'Password reset email sent'
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return JsonResponseHelper::standardResponse(
                400,
                null,
                'Invalid token'
            );
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return JsonResponseHelper::standardResponse(
            200,
            null,
            'Password has been reset successfully'
        );
    }


    public function tokenPayload(Request $request)
    {
        try {
            // Get token from Authorization: Bearer <token>
            $token = \Tymon\JWTAuth\Facades\JWTAuth::getToken();
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 400);
            }

            // Decode payload
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::getPayload($token)->toArray();

            return response()->json([
                'success' => true,
                'payload' => $payload,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }
    }



    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
