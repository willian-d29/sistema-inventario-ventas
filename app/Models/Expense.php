<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        "amount" => "double",
        'paid_from_cash_register' => 'boolean',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function cashMovement(): HasOne
    {
        return $this->hasOne(CashMovement::class);
    }
}
