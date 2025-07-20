<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - LARATORY</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11.5px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            height: 60px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 22px;
            color: #2c3e50;
        }
        .meta {
            margin-bottom: 20px;
        }
        .meta td {
            padding: 4px 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f4f6f8;
            text-align: center;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        td {
            font-size: 11.5px;
            text-align: center;
        }
        .total {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 40px;
            font-size: 10px;
            text-align: center;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path('assets/laratory-logo-zip-file/png/logo-no-background.png') }}" alt="LARATORY Logo">
        <h2>LARATORY - Reporte de Ventas</h2>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Fecha de Generación:</strong></td>
            <td>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Total de Ventas:</strong></td>
            <td>S/. {{ number_format($ventas->sum('total'), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total de Pedidos:</strong></td>
            <td>{{ $ventas->count() }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>N° Orden</th>
                <th>Total (S/.)</th>
                <th>Pagado (S/.)</th>
                <th>Deuda (S/.)</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $i => $venta)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $venta->customer->name ?? 'No registrado' }}</td>
                    <td>{{ $venta->order_number }}</td>
                    <td>{{ number_format($venta->total, 2) }}</td>
                    <td>{{ number_format($venta->paid, 2) }}</td>
                    <td>{{ number_format($venta->due, 2) }}</td>
                    <td>{{ ucfirst($venta->status) }}</td>
                    <td>{{ \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">Totales:</td>
                <td>S/. {{ number_format($ventas->sum('total'), 2) }}</td>
                <td>S/. {{ number_format($ventas->sum('paid'), 2) }}</td>
                <td>S/. {{ number_format($ventas->sum('due'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} LARATORY. Sistema de Inventario y Ventas - Reporte generado automáticamente.
    </div>

</body>
</html>
