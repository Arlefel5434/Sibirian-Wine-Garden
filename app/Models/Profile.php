<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = ['phone_number', 'avatar_path', 'bio'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
