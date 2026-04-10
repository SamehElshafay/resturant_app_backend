<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'role_id',
        'pos_id',
        'account_id',
        'phone',
        'address',
        'image',
        'salary',
        'reward_points',
        'commission_rate',
        'is_active',
        'account_code',
        'pin',
        'pos_account_code',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Virtual attribute for the first role name.
     */
    public function getRoleAttribute()
    {
        // Performance Tip: Ensure roles are eager-loaded
        return $this->role?->name ?? $this->roles->first()?->name;
    }

    public function posDevice()
    {
        return $this->belongsTo(PosDevice::class, 'pos_id');
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

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
