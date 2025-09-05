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
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'] ?? 'user',
        ]);

        // Create Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;
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
        $token = $user->createToken('auth_token')->plainTextToken;

        return JsonResponseHelper::standardResponse(
            200,
            [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'Login successful'
        );
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
