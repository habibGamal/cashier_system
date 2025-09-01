<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        /* TODO: Please implement your own logic here. */
        return true; // str_ends_with($this->email, '@larament.test');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isViewer(): bool
    {
        return $this->role === UserRole::VIEWER;
    }

    public function canManageOrders(): bool
    {
        return $this->role?->canManageOrders() ?? false;
    }

    public function canCancelOrders(): bool
    {
        return $this->role?->canCancelOrders() ?? false;
    }

    public function canApplyDiscounts(): bool
    {
        return $this->role?->canApplyDiscounts() ?? false;
    }
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function returnPurchaseInvoices()
    {
        return $this->hasMany(ReturnPurchaseInvoice::class);
    }

    public function wastes()
    {
        return $this->hasMany(Waste::class);
    }

    public function stocktaking()
    {
        return $this->hasMany(Stocktaking::class);
    }
}
