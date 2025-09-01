<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\SettingKey;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'driver_id',
        'user_id',
        'shift_id',
        'type',
        'status',
        'sub_total',
        'tax',
        'service',
        'discount',
        'temp_discount_percent',
        'total',
        'profit',
        'web_pos_diff',
        'payment_status',
        'dine_table_number',
        'kitchen_notes',
        'order_notes',
        'order_number',
    ];

    protected $appends = [
        'service_rate',
    ];

    protected $casts = [
        'type' => OrderType::class,
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'sub_total' => 'decimal:2',
        'tax' => 'decimal:2',
        'service' => 'decimal:2',
        'discount' => 'decimal:2',
        'temp_discount_percent' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function getServiceRateAttribute(): float
    {
        if ($this->type === OrderType::DINE_IN) {
            $settingsService = app(SettingsService::class);
            return (float) setting(SettingKey::DINE_IN_SERVICE_CHARGE);
        }

        return 0.0;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function table(): HasOne
    {
        return $this->hasOne(DineTable::class);
    }

    // Computed attributes
    public function getTypeStringAttribute(): string
    {
        return $this->type->label();
    }

    public function getStatusStringAttribute(): string
    {
        return $this->status->label();
    }

    public function getPaymentStatusStringAttribute(): string
    {
        return $this->payment_status->label();
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total - $this->total_paid);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->payment_status->isFullyPaid();
    }

    public function getCanBeModifiedAttribute(): bool
    {
        return $this->status->canBeModified();
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return $this->status->canBeCancelled();
    }
}
