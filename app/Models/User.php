<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */

     use HasFactory , Notifiable;
    public function createToken(string $type = 'access_token', array $customClaims = [])
    {
        if ($type === 'refresh_token') {
            // Generate a random refresh token
            $refreshToken = Str::random(64);

            // Store in DB (table: refresh_tokens)
            DB::update(
                'update refresh_tokens set is_refresh = ? where user_id = ?',
                [true,$this->id]
            );
            DB::table('refresh_tokens')->insert([
                'user_id'    => $this->id,
                'name' => '',
                'token'      => hash('sha256', $refreshToken), // hash for safety
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
            ]);

            return $refreshToken;
        }

        // Otherwise, generate a JWT access token
        return JWTAuth::fromUser($this);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // optional: include role in token
        ];
    }




    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }



    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
