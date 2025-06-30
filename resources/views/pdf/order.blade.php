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
            border-bottom: 2px solid #3490dc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #3490dc;
            font-size: 24px;
        }
        .section-title {
            background-color: #f1f5f9;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #3490dc;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
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
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .total {
            text-align: right;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Comprobante de Pedido</h1>
        <p style="margin: 0;">Pedido #{{ $order->id }}</p>
        <p style="margin: 0;">Fecha: {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</p>
    </div>

    <div class="section-title">Datos del Cliente</div>
    <div class="info">
        <p><strong>Nombre:</strong> {{ $order->customer->name ?? 'Cliente no disponible' }}</p>
        <p><strong>Email:</strong> {{ $order->customer->email ?? '-' }}</p>
        <p><strong>Teléfono:</strong> {{ $order->customer->phone ?? '-' }}</p>
        <p><strong>Dirección:</strong> {{ $order->customer->address ?? '-' }}</p>
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

    <div cla
