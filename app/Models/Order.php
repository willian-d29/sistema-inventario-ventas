<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Barryvdh\DomPDF\Facade\Pdf; // ✅ Esta es la correcta


class Order extends Model
{
    use HasFactory;

/*    protected $guarded = ['id'];

    protected $casts = [
        "sub_total"      => "double",
        "tax_total"      => "double",
        "discount_total" => "double",
        "total"          => "double",
        "paid"           => "double",
        "due"            => "double",
        "profit"         => "double",
        "loss"           => "double",
    ];*/

    protected $fillable = [
    'order_number',
    'customer_id',
    'sub_total',
    'tax_total',
    'discount_total',
    'total',
    'paid',
    'due',
    'profit',
    'loss',
    'status',
    'stripe_session_id',
];


public function customer()
{
    return $this->belongsTo(User::class, 'customer_id');
}


    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
{
    return $this->hasMany(OrderItem::class);
}




public function downloadPDF($id)
{
    $order = Order::with('items.product')->findOrFail($id);

    $pdf = PDF::loadView('pdf.invoice', ['order' => $order]);
return $pdf->stream("orden-{$order->order_number}.pdf");

}




}
