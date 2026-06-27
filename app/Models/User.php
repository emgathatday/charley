<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_verified',
        'verified_at',
        'verification_expires_at',
        'status',
        'last_login_at',
        'login_attempts',
        'locked_until',
        'mfa_enabled',
        'mfa_secret',
        'mfa_recovery_codes',
        'self_frozen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'mfa_enabled' => 'boolean',
            'mfa_recovery_codes' => 'array',
            'self_frozen_at' => 'datetime',
        ];
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(LoginToken::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function reviewedVerificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class, 'reviewed_by');
    }

    public function verificationReminderSchedules(): HasMany
    {
        return $this->hasMany(VerificationReminderSchedule::class);
    }

    public function activityFeed(): HasMany
    {
        return $this->hasMany(UserActivityFeed::class);
    }

    public function metas(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
