<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .company {
            font-size: 16px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 10px;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: left;
        }
        .items th {
            background-color: #f2f2f2;
        }
        .totals {
            margin-top: 10px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 4px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            color: #777;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="company">Mix Market</div>
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
            @php $product = json_decode($item->product_json, true); @endphp
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
            <td><strong>Subtotal:</strong></td>
            <td>S/ {{ number_format($order->sub_total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Impuesto:</strong></td>
            <td>S/ {{ number_format($order->tax_total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Descuento:</strong></td>
            <td>- S/ {{ number_format($order->discount_total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td><strong>S/ {{ number_format($order->total, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Pagado:</strong></td>
            <td>S/ {{ number_format($order->paid, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Deuda:</strong></td>
            <td>S/ {{ number_format($order->due, 2) }}</td>
        </tr>
    </table>
</div>

<div class="footer">
    Gracias por su compra - Mix Market
</div>

</body>
</html>
