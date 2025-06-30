<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
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
            background-color: #f2f2f2;
            text-align: left;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
        }
        .total {
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>

    <h1>Reporte de Ventas</h1>

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
            <td><strong>Ventas Registradas:</strong></td>
            <td>{{ $ventas->count() }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Número de Orden</th>
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
        </tbody>
    </table>

</body>
</html>
