<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pedido #{{ $order->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-box {
            display: inline-block;
            background-color: #1a1a1a;
            padding: 8px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .logo-box img {
            height: 50px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #3490dc;
            margin-top: 5px;
        }

        .date {
            font-size: 13px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }

        .deleted-product {
            color: red;
            font-style: italic;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f9fafb;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-box">
            <img src="{{ public_path('assets/laratory-logo-zip-file/png/logo-no-background.png') }}" alt="Logo">
        </div>
        <div class="title">Comprobante de Pedido</div>
        <div class="date">Pedido #{{ $order->id }} | Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td>
                        @if($item->product)
                            {{ $item->product->name }}
                        @else
                            <span class="deleted-product">Producto eliminado</span>
                        @endif
                    </td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>S/. {{ number_format($item->price ?? 0, 2) }}</td>
                    <td>S/. {{ number_format($item->total ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No hay productos registrados en esta orden.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total a Pagar:</td>
                <td>S/. {{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Mix Market - ¡Gracias por confiar en nosotros!
    </div>

</body>
</html>
