<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #{{ $order->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #3490dc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo-container {
            background-color: #1a1a1a;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 6px;
        }

        .logo-container img {
            height: 50px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #3490dc;
            margin-top: 5px;
        }

        .meta {
            font-size: 13px;
            color: #555;
        }

        .section-title {
            background-color: #f1f5f9;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #3490dc;
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #3490dc;
            color: #fff;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .total {
            text-align: right;
            font-size: 14px;
            margin-top: 15px;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: #777;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-container">
            <img src="{{ public_path('assets/laratory-logo-zip-file/png/logo-no-background.png') }}" alt="Logo">
        </div>
        <div class="title">Comprobante de Pedido</div>
        <div class="meta">Pedido #{{ $order->id }} | Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <div class="section-title">Datos del Cliente</div>
    <div class="info">
        <strong>Nombre:</strong> {{ $order->customer->name ?? 'Cliente no disponible' }}<br>
        <strong>Email:</strong> {{ $order->customer->email ?? '-' }}<br>
        <strong>Teléfono:</strong> {{ $order->customer->phone ?? '-' }}<br>
        <strong>Dirección:</strong> {{ $order->customer->address ?? '-' }}
    </div>

    <div class="section-title">Detalles del Pedido</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>S/. {{ number_format($item->price, 2) }}</td>
                    <td>S/. {{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <strong>Total Pagado:</strong> S/. {{ number_format($order->paid, 2) }}<br>
        <strong>Deuda:</strong> S/. {{ number_format($order->due, 2) }}
    </div>

    <div class="footer">
        Gracias por confiar en Mix Market — Tu pedido será procesado pronto.
    </div>

</body>
</html>
