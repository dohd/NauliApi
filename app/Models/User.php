<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

// use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'password',
        'rel_id',
        'active',
        'pass_reset_otp',
        'pass_otp_exp',
        'withdraw_otp',
        'withdraw_otp_exp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pass_reset_otp',
        'pass_otp_exp',
        'withdraw_otp',
        'withdraw_otp_exp'
    ];

    /**
     * Set password attribute.
     *
     * @param [string] $password
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    /**
     * Get account owner attribute.
     *
     * @param [string] $password
     */
    public function getOwnerIdAttribute()
    {
        return $this->rel_id ?: $this->id;
    }
}
