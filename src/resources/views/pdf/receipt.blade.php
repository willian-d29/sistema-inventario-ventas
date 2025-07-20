<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3490dc;
        }
        .logo {
            display: block;
            margin: 0 auto 10px;
            width: 100px;
            height: auto;
            background-color: #000;
            padding: 8px;
            border-radius: 10px;
        }
        .company {
            font-size: 20px;
            font-weight: bold;
            color: #3490dc;
        }
        .section {
            margin-bottom: 15px;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items th, .items td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        .items th {
            background-color: #3490dc;
            color: #fff;
        }
        .totals {
            margin-top: 10px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 6px;
            font-weight: bold;
            text-align: right;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 30px;
            color: #555;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('assets/laratory-logo-zip-file/png/logo-no-background.png') }}" alt="Laratoy Logo" class="logo">
    <div class="company">Laratoy</div>
    <div>RUC: 12345678901</div>
    <div>Av. Comercial 123 - Puno, Perú</div>
    <div>Tel: (051) 123456</div>
</div>

<div class="section">
    <strong>Comprobante de Pago</strong><br>
    N°: {{ $order->order_number }}<br>
    Fecha: {{ $order->created_at->format('d/m/Y H:i') }}<br>
    Cliente: {{ $order->customer?->name ?? 'Consumidor final' }}<br>
</div>

<div class="items">
    <table>
        <thead>
        <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Cant.</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            @php
                $product = is_array($item->product_json)
                    ? $item->product_json
                    : json_decode($item->product_json, true);
            @endphp
            <tr>
                <td>{{ $product['name'] }}</td>
                <td>S/ {{ number_format($product['selling_price'], 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>S/ {{ number_format($product['selling_price'] * $item->quantity, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="totals">
    <table>
        <tr>
            <td>Subtotal:</td>
            <td>S/ {{ number_format($order->sub_total, 2) }}</td>
        </tr>
        <tr>
            <td>Impuesto:</td>
            <td>S/ {{ number_format($order->tax_total, 2) }}</td>
        </tr>
        <tr>
            <td>Descuento:</td>
            <td>- S/ {{ number_format($order->discount_total, 2) }}</td>
        </tr>
        <tr>
            <td>Total:</td>
            <td>S/ {{ number_format($order->total, 2) }}</td>
        </tr>
        <tr>
            <td>Pagado:</td>
            <td>S/ {{ number_format($order->paid, 2) }}</td>
        </tr>
        <tr>
            <td>Deuda:</td>
            <td>S/ {{ number_format($order->due, 2) }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    ¡Gracias por confiar en <strong>Laratoy</strong>!<br>
    Este documento es válido como comprobante de pago.
</div>

</body>
</html>
