<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Client extends Model
{
    use HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'phone',
        'photo_url',
        'provider',
        'provider_id',
        'device_token',
        'is_active',
        'status',
        'activation_expires_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'device_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activation_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Check if client is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if client is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isActivationExpired();
    }

    /**
     * Check if client is banned
     *
     * @return bool
     */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /**
     * Check if client subscription is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if activation is expired
     *
     * @return bool
     */
    public function isActivationExpired(): bool
    {
        if (!$this->activation_expires_at) {
            return false;
        }
        return now()->isAfter($this->activation_expires_at);
    }

    /**
     * Activate client for specified months
     *
     * @param int $months
     * @return bool
     */
    public function activate(int $months): bool
    {
        return $this->update([
            'status' => 'active',
            'activation_expires_at' => now()->addMonths($months),
        ]);
    }

    /**
     * Ban client
     *
     * @return bool
     */
    public function ban(): bool
    {
        return $this->update([
            'status' => 'banned',
            'activation_expires_at' => null,
        ]);
    }

    /**
     * Set client to pending
     *
     * @return bool
     */
    public function setPending(): bool
    {
        return $this->update([
            'status' => 'pending',
            'activation_expires_at' => null,
        ]);
    }

    /**
     * Set client to expired
     *
     * @return bool
     */
    public function setExpired(): bool
    {
        return $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Get the products for the client.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
