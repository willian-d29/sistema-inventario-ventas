<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        try {
            // Establece la clave secreta de Stripe
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Recoge los productos del carrito
            $cartItems = collect($request->input('items'));

            if ($cartItems->isEmpty()) {
                return response()->json(['error' => 'El carrito está vacío.'], 400);
            }

            // Formatea los productos para Stripe
            $lineItems = $cartItems->map(function ($item) {
                $unitAmount = (int)($item['selling_price'] * 100); // Stripe usa centavos

                if ($unitAmount < 1) {
                    throw new \Exception("Precio inválido para el producto: {$item['name']}");
                }

                return [
                    'price_data' => [
                        'currency' => 'pen',
                        'product_data' => ['name' => $item['name']],
                        'unit_amount' => $unitAmount,
                    ],
                    'quantity' => $item['quantity'],
                ];
            });

            // Cálculo total para la orden
            $total = $cartItems->sum(fn($item) => $item['quantity'] * $item['selling_price']);

            // Crea sesión de pago con Stripe
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems->toArray(),
                'mode' => 'payment',
                'success_url' => url('/checkout/success'),
                'cancel_url' => url('/checkout/cancel'),
            ]);

            // Generar número único de orden (puedes personalizarlo)
            $orderNumber = 'ORD-' . now()->format('YmdHis');

            // Crear la orden en tu base de datos
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => Auth::id(),
                'sub_total' => $total,
                'total' => $total,
                'paid' => 0,
                'due' => $total,
                    'profit' => 0, // 
                    'loss' => 0, //
                        'status' => 'pendiente', //



                'stripe_session_id' => $session->id,
            ]);

            // Insertar los productos de la orden
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['selling_price'],
                    'total' => $item['quantity'] * $item['selling_price'],
                        'product_json' => json_encode($item), // <- Aquí agregas el campo requerido

                ]);
            }

            return response()->json(['id' => $session->id]);

        } catch (\Exception $e) {
            \Log::error('Error en el pago con Stripe: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Hubo un error al procesar el pago. Intenta nuevamente.',
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    
public function success(Request $request, OrderService $orderService)
{
    $order = \App\Models\Order::where('customer_id', auth()->id())
        ->latest()
        ->first();

    if (!$order) {
        return redirect('/')->with('error', 'No se encontró la orden.');
    }

    $orderService->pay($order->id, [
        'amount' => $order->total,
        'paid_through' => 'card',
    ]);

    return redirect('/cliente/pedidos')->with('success', 'Pago registrado correctamente.');
}



}
