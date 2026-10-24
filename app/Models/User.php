<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'first_name', 'last_name', 'email', 'phone', 'password',
    'birth_date', 'preferred_locale',
    'no_show_count', 'blocked_at', 'block_reason', 'prepayment_required',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'blocked_at' => 'datetime',
            'no_show_count' => 'integer',
            'prepayment_required' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['super_admin', 'gestionnaire', 'agent_accueil']);
    }

    public function displayName(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->name ?: (string) $this->email);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name ?: '?', 0, 1).mb_substr($this->last_name ?: '', 0, 1));
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    /** Nombre de réservations qui immobilisent encore un véhicule. */
    public function activeBookingsCount(): int
    {
        return $this->bookings()->activeBookings()->count();
    }
}
