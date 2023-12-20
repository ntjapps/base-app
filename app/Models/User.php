<?php

namespace App\Models;

use App\Interfaces\PermissionConst;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Laravel\Pennant\Concerns\HasFeatures;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements PermissionConst
{
    use HasApiTokens, HasFactory, HasFeatures, HasRoles, HasUuids, Notifiable, Prunable, SoftDeletes;

    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * Exclude constant permission
     */
    public function exceptConstPermission(): array
    {
        return static::ALLPERM;
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'totp_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'email',
        'email_verified_at',
        'password',
        'totp_key',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'totp_key' => 'encrypted',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'user_permission',
    ];

    /**
     * Get user_permission attribute
     */
    protected function userPermission(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $this->getAllPermissions()->pluck('name')->toArray(),
        )->shouldCache();
    }
}
