<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $documentLabels = $locale === 'en'
        ? ['sale_note' => 'Document', 'receipt' => 'Receipt', 'invoice' => 'Invoice']
        : ['sale_note' => 'Comprobante', 'receipt' => 'Boleta', 'invoice' => 'Factura'];
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $allLabel = $locale === 'en' ? 'All' : 'Todos';
    $labels = $locale === 'en'
        ? [
            'title' => 'Sales Report',
            'generated_at' => 'Generated at',
            'range' => 'Range',
            'start' => 'Start',
            'current' => 'Current',
            'total_sold' => 'Total sold',
            'documents' => 'Documents',
            'net_product_income' => 'Net product income',
            'quantity_sold' => 'Quantity sold',
            'cost_sold' => 'Cost sold',
            'gross_profit' => 'Gross profit',
            'discounts' => 'Discounts',
            'filters' => 'Filters',
            'cashier' => 'Cashier',
            'payment' => 'payment',
            'product' => 'product',
            'document_filter' => 'document',
            'document' => 'Document',
            'receipt' => 'Receipt',
            'payments' => 'Payments',
            'date' => 'Date',
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'cost' => 'Cost',
            'profit' => 'Profit',
            'total' => 'Total',
            'collections_by_method' => 'Collections by payment method',
            'method' => 'Method',
            'operations' => 'Operations',
            'no_collections' => 'No collections recorded.',
            'sales_by_product' => 'Sales by product',
            'quantity' => 'Quantity',
            'income' => 'Income',
            'margin' => 'Margin',
            'no_products' => 'No products sold.',
            'sales_by_category' => 'Sales by category',
            'category' => 'Category',
            'no_categories' => 'No sales by category.',
            'sales_by_cashier' => 'Sales by cashier',
            'no_cashiers' => 'No sales by cashier.',
            'sales_by_document' => 'Sales by document type',
            'no_documents' => 'No documents.',
            'no_cashier' => 'No cashier',
            'notice' => 'Internal document. Not valid as a tax receipt',
        ]
        : [
            'title' => 'Reporte de Ventas',
            'generated_at' => 'Fecha de generación',
            'range' => 'Rango',
            'start' => 'Inicio',
            'current' => 'Actual',
            'total_sold' => 'Total vendido',
            'documents' => 'Comprobantes',
            'net_product_income' => 'Ingreso neto productos',
            'quantity_sold' => 'Cantidad vendida',
            'cost_sold' => 'Costo vendido',
            'gross_profit' => 'Utilidad bruta',
            'discounts' => 'Descuentos',
            'filters' => 'Filtros',
            'cashier' => 'Cajero',
            'payment' => 'pago',
            'product' => 'producto',
            'document_filter' => 'documento',
            'document' => 'Documento',
            'receipt' => 'Comprobante',
            'payments' => 'Pagos',
            'date' => 'Fecha',
            'subtotal' => 'Subtotal',
            'discount' => 'Descuento',
            'cost' => 'Costo',
            'profit' => 'Utilidad',
            'total' => 'Total',
            'collections_by_method' => 'Cobros por método de pago',
            'method' => 'Método',
            'operations' => 'Operaciones',
            'no_collections' => 'Sin cobros registrados.',
            'sales_by_product' => 'Ventas por producto',
            'quantity' => 'Cantidad',
            'income' => 'Ingreso',
            'margin' => 'Margen',
            'no_products' => 'Sin productos vendidos.',
            'sales_by_category' => 'Ventas por categoría',
            'category' => 'Categoría',
            'no_categories' => 'Sin ventas por categoría.',
            'sales_by_cashier' => 'Ventas por cajero',
            'no_cashiers' => 'Sin ventas por cajero.',
            'sales_by_document' => 'Ventas por tipo de documento',
            'no_documents' => 'Sin comprobantes.',
            'no_cashier' => 'Sin cajero',
            'notice' => 'Documento interno. No válido como comprobante tributario',
        ];
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $labels['title'] }}</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; color: #1f2937; background: #ffffff; }
        h1, h2, p { margin: 0; }
        h2 { color: #111827; font-size: 13px; margin-top: 18px; padding-left: 8px; border-left: 4px solid #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 9px; }
        th { background-color: #eff6ff; color: #1e40af; font-size: 9px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #dbe3ef; padding: 6px; vertical-align: top; }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .hero { background: #1d4ed8; color: #ffffff; border-radius: 10px; padding: 18px; }
        .brand { color: #bfdbfe; font-size: 10px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .hero h1 { font-size: 24px; margin-top: 4px; }
        .hero p { color: #dbeafe; font-size: 10px; margin-top: 6px; }
        .summary td { border: none; padding: 0; }
        .metric { border: 1px solid #dbe3ef; border-radius: 8px; padding: 10px; background-color: #f8fafc; }
        .metric span { display: block; color: #64748b; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .metric strong { display: block; color: #111827; font-size: 15px; margin-top: 4px; }
        .metric.profit strong { color: #047857; }
        .metric.discount strong { color: #b45309; }
        .filters { border: 1px solid #dbe3ef; border-radius: 8px; margin-top: 10px; padding: 9px; background-color: #ffffff; }
        .filters strong { color: #111827; }
        .notice { margin-top: 14px; text-align: center; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="brand">LaraTory</div>
        <h1>{{ $labels['title'] }}</h1>
        <p>{{ $labels['generated_at'] }}: {{ now()->format('d/m/Y H:i') }} · {{ $labels['range'] }}: {{ $filters['date_from'] ?? $labels['start'] }} - {{ $filters['date_to'] ?? $labels['current'] }}</p>
    </div>

    <table class="summary">
        <tr>
            <td style="width: 25%; padding: 10px 6px 0 0;">
                <div class="metric">
                    <span>{{ $labels['total_sold'] }}</span>
                    <strong>S/ {{ number_format($ventas->sum('total'), 2) }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 10px 6px 0 0;">
                <div class="metric profit">
                    <span>{{ $labels['gross_profit'] }}</span>
                    <strong>S/ {{ number_format($itemSummary['gross_profit'], 2) }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 10px 6px 0 0;">
                <div class="metric">
                    <span>{{ $labels['documents'] }}</span>
                    <strong>{{ $ventas->count() }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 10px 0 0 0;">
                <div class="metric discount">
                    <span>{{ $labels['discounts'] }}</span>
                    <strong>S/ {{ number_format($itemSummary['discount'], 2) }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td style="width: 25%; padding: 8px 6px 0 0;">
                <div class="metric">
                    <span>{{ $labels['net_product_income'] }}</span>
                    <strong>S/ {{ number_format($itemSummary['income'], 2) }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 8px 6px 0 0;">
                <div class="metric">
                    <span>{{ $labels['cost_sold'] }}</span>
                    <strong>S/ {{ number_format($itemSummary['cost'], 2) }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 8px 6px 0 0;">
                <div class="metric">
                    <span>{{ $labels['quantity_sold'] }}</span>
                    <strong>{{ number_format($itemSummary['quantity'], 0) }}</strong>
                </div>
            </td>
            <td style="width: 25%; padding: 8px 0 0 0;">
                <div class="metric profit">
                    <span>{{ $labels['margin'] }}</span>
                    <strong>{{ number_format($itemSummary['margin'], 2) }}%</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="filters">
        <strong>{{ $labels['filters'] }}:</strong>
        {{ $labels['cashier'] }}: {{ $filters['cashier_id'] ?? $allLabel }},
        {{ $labels['payment'] }}: {{ $paymentLabels[$filters['payment_method'] ?? ''] ?? $allLabel }},
        {{ $labels['product'] }}: {{ $filters['product_id'] ?? $allLabel }},
        {{ $labels['document_filter'] }}: {{ $documentLabels[$filters['document_type'] ?? ''] ?? $allLabel }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $labels['receipt'] }}</th>
                <th>{{ $labels['cashier'] }}</th>
                <th>{{ $labels['payments'] }}</th>
                <th class="right">{{ $labels['subtotal'] }}</th>
                <th class="right">{{ $labels['discount'] }}</th>
                <th class="right">{{ $labels['cost'] }}</th>
                <th class="right">{{ $labels['profit'] }}</th>
                <th class="right">IGV</th>
                <th class="right">{{ $labels['total'] }}</th>
                <th>{{ $labels['date'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $i => $venta)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $venta->full_document_number }}</strong><br>
                        <span class="muted">{{ $documentLabels[$venta->document_type] ?? $venta->document_type }}</span>
                    </td>
                    <td>{{ $venta->cashier->name ?? $labels['no_cashier'] }}</td>
                    <td>{{ $venta->payments->pluck('payment_method')->unique()->map(fn ($method) => $paymentLabels[$method] ?? $method)->join(' + ') }}</td>
                    <td class="right">{{ number_format($venta->subtotal, 2) }}</td>
                    <td class="right">{{ number_format($venta->discount_total, 2) }}</td>
                    <td class="right">{{ number_format($venta->items->sum('cost_subtotal'), 2) }}</td>
                    <td class="right">{{ number_format($venta->items->sum('gross_profit'), 2) }}</td>
                    <td class="right">{{ number_format($venta->igv, 2) }}</td>
                    <td class="right">{{ number_format($venta->total, 2) }}</td>
                    <td>{{ optional($venta->sold_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ $labels['collections_by_method'] }}</h2>
    <table>
        <thead>
            <tr><th>{{ $labels['method'] }}</th><th class="right">{{ $labels['operations'] }}</th><th class="right">{{ $labels['total'] }}</th></tr>
        </thead>
        <tbody>
            @forelse($paymentSummary as $payment)
                <tr>
                    <td>{{ $payment['label'] }}</td>
                    <td class="right">{{ $payment['count'] }}</td>
                    <td class="right">S/ {{ number_format($payment['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ $labels['no_collections'] }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ $labels['sales_by_product'] }}</h2>
    <table>
        <thead>
            <tr><th>{{ $labels['product'] }}</th><th class="right">{{ $labels['quantity'] }}</th><th class="right">{{ $labels['income'] }}</th><th class="right">{{ $labels['cost'] }}</th><th class="right">{{ $labels['profit'] }}</th><th class="right">{{ $labels['margin'] }}</th><th class="right">{{ $labels['discount'] }}</th></tr>
        </thead>
        <tbody>
            @forelse($productSummary as $product)
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td class="right">{{ number_format($product['quantity'], 0) }}</td>
                    <td class="right">S/ {{ number_format($product['total'], 2) }}</td>
                    <td class="right">S/ {{ number_format($product['cost'], 2) }}</td>
                    <td class="right">S/ {{ number_format($product['gross_profit'], 2) }}</td>
                    <td class="right">{{ number_format($product['margin'], 2) }}%</td>
                    <td class="right">S/ {{ number_format($product['discount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ $labels['no_products'] }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ $labels['sales_by_category'] }}</h2>
    <table>
        <thead>
            <tr><th>{{ $labels['category'] }}</th><th class="right">{{ $labels['quantity'] }}</th><th class="right">{{ $labels['income'] }}</th><th class="right">{{ $labels['cost'] }}</th><th class="right">{{ $labels['profit'] }}</th><th class="right">{{ $labels['margin'] }}</th></tr>
        </thead>
        <tbody>
            @forelse($categorySummary as $category)
                <tr>
                    <td>{{ $category['name'] }}</td>
                    <td class="right">{{ number_format($category['quantity'], 0) }}</td>
                    <td class="right">S/ {{ number_format($category['total'], 2) }}</td>
                    <td class="right">S/ {{ number_format($category['cost'], 2) }}</td>
                    <td class="right">S/ {{ number_format($category['gross_profit'], 2) }}</td>
                    <td class="right">{{ number_format($category['margin'], 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ $labels['no_categories'] }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ $labels['sales_by_cashier'] }}</h2>
    <table>
        <thead>
            <tr><th>{{ $labels['cashier'] }}</th><th class="right">{{ $labels['documents'] }}</th><th class="right">{{ $labels['total'] }}</th></tr>
        </thead>
        <tbody>
            @forelse($cashierSummary as $cashier)
                <tr>
                    <td>{{ $cashier['name'] }}</td>
                    <td class="right">{{ $cashier['count'] }}</td>
                    <td class="right">S/ {{ number_format($cashier['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ $labels['no_cashiers'] }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ $labels['sales_by_document'] }}</h2>
    <table>
        <thead>
            <tr><th>{{ $labels['document'] }}</th><th class="right">{{ $labels['documents'] }}</th><th class="right">{{ $labels['total'] }}</th></tr>
        </thead>
        <tbody>
            @forelse($documentSummary as $document)
                <tr>
                    <td>{{ $document['label'] }}</td>
                    <td class="right">{{ $document['count'] }}</td>
                    <td class="right">S/ {{ number_format($document['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ $labels['no_documents'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="notice">{{ $labels['notice'] }}.</p>
</body>
</html>
