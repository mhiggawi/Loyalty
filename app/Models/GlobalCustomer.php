<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class GlobalCustomer extends Model
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $fillable = [
        'phone_number',
        'email',
        'full_name',
        'date_of_birth',
        'password_hash',
        'device_token',
        'language',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Get all memberships for this customer.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    /**
     * Check if customer has email verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if customer has phone verified.
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Mark email as verified.
     */
    public function markEmailAsVerified(): void
    {
        $this->email_verified_at = now();
        $this->save();
    }

    /**
     * Mark phone as verified.
     */
    public function markPhoneAsVerified(): void
    {
        $this->phone_verified_at = now();
        $this->save();
    }

    /**
     * Get customer's preferred language.
     */
    public function getPreferredLanguage(): string
    {
        return $this->language ?? 'ar';
    }

    /**
     * Check if it's customer's birthday today.
     */
    public function isBirthdayToday(): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->month === now()->month &&
               $this->date_of_birth->day === now()->day;
    }

    /**
     * Get customer's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Scope: Customers who have birthday this month.
     */
    public function scopeBirthdayThisMonth($query)
    {
        return $query->whereMonth('date_of_birth', now()->month);
    }

    /**
     * Scope: Customers who have birthday today.
     */
    public function scopeBirthdayToday($query)
    {
        return $query->whereMonth('date_of_birth', now()->month)
                     ->whereDay('date_of_birth', now()->day);
    }
}
