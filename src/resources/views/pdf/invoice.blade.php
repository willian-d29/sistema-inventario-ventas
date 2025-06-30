<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pedido</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h2, h3 { margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row td { font-weight: bold; }
        .deleted-product { color: red; font-style: italic; }
    </style>
</head>
<body>
    <h2>Comprobante de Pedido #{{ $order->id }}</h2>
    <p>Fecha: {{ $order->created_at->format('d/m/Y') }}</p>

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
                <td colspan="3" style="text-align: right;">Total:</td>
                <td>S/. {{ number_format($order->total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
