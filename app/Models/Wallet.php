<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'currency'];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(WalletReservation::class);
    }

    public function availableBalance(): int
    {
        $reserved = $this->relationLoaded('reservations')
            ? $this->reservations->where('status', 'reserved')->sum('amount')
            : $this->reservations()->where('status', 'reserved')->sum('amount');

        return max(0, $this->balance - $reserved);
    }
}
