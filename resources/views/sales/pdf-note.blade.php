<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $labels = $locale === 'en'
        ? [
            'sale_note' => 'DOCUMENT',
            'receipt' => 'RECEIPT',
            'invoice' => 'INVOICE',
            'ruc' => 'Tax ID',
            'phone' => 'Phone',
            'date' => 'Date',
            'cashier' => 'Cashier',
            'internal_id' => 'Internal ID',
            'product' => 'Product',
            'code' => 'Code',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit price',
            'discount' => 'Discount',
            'amount' => 'Amount',
            'tax' => 'Informational tax',
            'total' => 'Total',
            'payments' => 'Payments',
            'method' => 'Method',
            'reference' => 'Reference',
            'bank' => 'Bank',
            'received' => 'Received',
            'change' => 'Change',
            'notice' => 'Internal document. Not valid as a tax receipt',
        ]
        : [
            'sale_note' => 'COMPROBANTE',
            'receipt' => 'BOLETA',
            'invoice' => 'FACTURA',
            'ruc' => 'RUC',
            'phone' => 'Tel',
            'date' => 'Fecha',
            'cashier' => 'Cajero',
            'internal_id' => 'ID interno',
            'product' => 'Producto',
            'code' => 'Código',
            'quantity' => 'Cantidad',
            'unit_price' => 'P. Unitario',
            'discount' => 'Descuento',
            'amount' => 'Importe',
            'tax' => 'Imp. informativo',
            'total' => 'Total',
            'payments' => 'Pagos',
            'method' => 'Método',
            'reference' => 'Referencia',
            'bank' => 'Banco',
            'received' => 'Recibido',
            'change' => 'Vuelto',
            'notice' => 'Documento interno. No válido como comprobante tributario',
        ];
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $documentTitle = $labels[$sale->document_type] ?? strtoupper((string) $sale->document_type);
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $sale->full_document_number }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1, h2, p { margin: 0; }
        .header { display: table; width: 100%; border-bottom: 2px solid #111827; padding-bottom: 14px; }
        .left, .right { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #4b5563; }
        .box { border: 1px solid #d1d5db; padding: 10px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .totals { width: 260px; margin-left: auto; margin-top: 18px; }
        .notice { margin-top: 18px; padding: 12px; border: 1px solid #f59e0b; background: #fffbeb; font-weight: bold; }
        .logo { max-width: 110px; max-height: 70px; object-fit: contain; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="left">
            @if($business['logo_url'])
                <img src="{{ $business['logo_url'] }}" alt="Logo {{ $business['business_name'] }}" class="logo">
            @endif
            <h1>{{ $business['business_name'] }}</h1>
            @if($business['legal_name'])
                <p>{{ $business['legal_name'] }}</p>
            @endif
            @if($business['tax_id'])
                <p>{{ $labels['ruc'] }}: {{ $business['tax_id'] }}</p>
            @endif
            @if($business['address'])
                <p class="muted">{{ $business['address'] }}</p>
            @endif
            @if($business['phone'])
                <p class="muted">{{ $labels['phone'] }}: {{ $business['phone'] }}</p>
            @endif
            @if($business['email'])
                <p class="muted">{{ $business['email'] }}</p>
            @endif
        </div>
        <div class="right">
            <h2>
                {{ $documentTitle }}
            </h2>
            <p><strong>{{ $sale->full_document_number }}</strong></p>
        </div>
    </div>

    <div class="box">
        <p><strong>{{ $labels['date'] }}:</strong> {{ $formatDateTime($sale->sold_at) }}</p>
        <p><strong>{{ $labels['cashier'] }}:</strong> {{ $sale->cashier->name ?? '-' }}</p>
        <p><strong>{{ $business['cash_register_name'] }}:</strong> #{{ $sale->cash_register_id }}</p>
        <p><strong>{{ $labels['internal_id'] }}:</strong> #{{ $sale->id }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ $labels['product'] }}</th>
                <th>{{ $labels['code'] }}</th>
                <th class="text-right">{{ $labels['quantity'] }}</th>
                <th class="text-right">{{ $labels['unit_price'] }}</th>
                <th class="text-right">{{ $labels['discount'] }}</th>
                <th class="text-right">{{ $labels['amount'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product_name_snapshot }}</td>
                    <td>{{ $item->product_code_snapshot ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ $money($item->unit_price) }}</td>
                    <td class="text-right">{{ $money($item->discount) }}</td>
                    <td class="text-right">{{ $money((float) $item->subtotal - (float) $item->discount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">{{ $money($sale->subtotal) }}</td></tr>
        <tr><td>{{ $labels['discount'] }}</td><td class="text-right">{{ $money($sale->discount_total) }}</td></tr>
        @if((float) $sale->igv > 0)
            <tr><td>{{ $labels['tax'] }}</td><td class="text-right">{{ $money($sale->igv) }}</td></tr>
        @endif
        <tr><td><strong>{{ $labels['total'] }}</strong></td><td class="text-right"><strong>{{ $money($sale->total) }}</strong></td></tr>
    </table>

    <h3>{{ $labels['payments'] }}</h3>
    <table>
        <thead>
            <tr>
                <th>{{ $labels['method'] }}</th>
                <th>{{ $labels['reference'] }}</th>
                <th>{{ $labels['bank'] }}</th>
                <th class="text-right">{{ $labels['received'] }}</th>
                <th class="text-right">{{ $labels['change'] }}</th>
                <th class="text-right">{{ $labels['amount'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->payments as $payment)
                <tr>
                    <td>{{ $paymentLabels[$payment->payment_method] ?? $payment->payment_method }}</td>
                    <td>{{ $payment->operation_number ?? '-' }}</td>
                    <td>{{ $payment->bank_name ?? '-' }}</td>
                    <td class="text-right">{{ $payment->received_amount ? $money($payment->received_amount) : '-' }}</td>
                    <td class="text-right">{{ $payment->change_amount ? $money($payment->change_amount) : '-' }}</td>
                    <td class="text-right">{{ $money($payment->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="notice">
        {{ $labels['notice'] }}.
    </p>
    @if($business['receipt_footer'])
        <p class="muted" style="margin-top: 10px; text-align: center;">{{ $business['receipt_footer'] }}</p>
    @endif
</body>
</html>
