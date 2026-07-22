<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $labels = $locale === 'en'
        ? [
            'print' => 'Print',
            'sale_note' => 'DOCUMENT',
            'receipt' => 'RECEIPT',
            'invoice' => 'INVOICE',
            'phone' => 'Phone',
            'date' => 'Date',
            'cashier' => 'Cashier',
            'internal_id' => 'Internal ID',
            'product' => 'Product',
            'qty' => 'Qty',
            'amount' => 'Amount',
            'discount' => 'Discount',
            'tax' => 'Informational tax',
            'total' => 'Total',
            'each' => 'each',
            'received' => 'Received',
            'change' => 'Change',
            'operation' => 'Operation',
            'bank' => 'Bank',
            'notice' => 'Internal document. Not valid as a tax receipt',
            'requesting' => 'Registering request...',
            'opened' => 'Print dialog opened.',
            'failed' => 'Could not register the request. Try again.',
        ]
        : [
            'print' => 'Imprimir',
            'sale_note' => 'COMPROBANTE',
            'receipt' => 'BOLETA',
            'invoice' => 'FACTURA',
            'phone' => 'Tel',
            'date' => 'Fecha',
            'cashier' => 'Cajero',
            'internal_id' => 'ID interno',
            'product' => 'Producto',
            'qty' => 'Cant',
            'amount' => 'Importe',
            'discount' => 'Descuento',
            'tax' => 'Imp. informativo',
            'total' => 'Total',
            'each' => 'c/u',
            'received' => 'Recibido',
            'change' => 'Vuelto',
            'operation' => 'Operación',
            'bank' => 'Banco',
            'notice' => 'Documento interno. No válido como comprobante tributario',
            'requesting' => 'Registrando solicitud...',
            'opened' => 'Diálogo de impresión abierto.',
            'failed' => 'No se pudo registrar la solicitud. Intenta de nuevo.',
        ];
    $paymentLabels = $locale === 'en'
        ? ['cash' => 'Cash', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Card', 'transfer' => 'Transfer']
        : ['cash' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'];
    $documentTitle = $labels[$sale->document_type] ?? strtoupper((string) $sale->document_type);
@endphp
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $sale->full_document_number }}</title>
    <style>
        @page { size: {{ (int) $business['thermal_paper_width'] }}mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            width: {{ max((int) $business['thermal_paper_width'] - 8, 50) }}mm;
            margin: 0 auto;
            color: #111827;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: {{ (int) $business['thermal_paper_width'] === 80 ? 11 : 10 }}px;
            line-height: 1.3;
        }
        h1, h2, p { margin: 0; }
        .center { text-align: center; }
        .muted { color: #4b5563; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #111827; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .items { width: 100%; border-collapse: collapse; }
        .items th, .items td { padding: 2px 0; vertical-align: top; }
        .items th { border-bottom: 1px dashed #111827; text-align: left; }
        .right { text-align: right; }
        .logo { max-width: 32mm; max-height: 18mm; filter: grayscale(1); margin-bottom: 4px; }
        .notice {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #111827;
            font-weight: 700;
            text-align: center;
        }
        @media print {
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions center" style="margin: 8px 0;">
        <button id="printButton" type="button">{{ $labels['print'] }}</button>
        <p id="printStatus" class="muted" style="margin-top: 4px;"></p>
    </div>

    <header class="center">
        @if($business['thermal_show_logo'] && $business['logo_url'])
            <img src="{{ $business['logo_url'] }}" alt="Logo {{ $business['business_name'] }}" class="logo">
        @endif
        <h1>{{ $business['business_name'] }}</h1>
        @if($business['legal_name'])
            <p>{{ $business['legal_name'] }}</p>
        @endif
        @if($business['tax_id'])
            <p>RUC: {{ $business['tax_id'] }}</p>
        @endif
        @if($business['address'])
            <p class="muted">{{ $business['address'] }}</p>
        @endif
        @if($business['phone'])
            <p class="muted">{{ $labels['phone'] }}: {{ $business['phone'] }}</p>
        @endif
        <h2 style="margin-top: 6px;">
            {{ $documentTitle }}
        </h2>
        <p class="bold">{{ $sale->full_document_number }}</p>
    </header>

    <div class="line"></div>

    <div>
        <div class="row"><span>{{ $labels['date'] }}:</span><span>{{ $formatDateTime($sale->sold_at) }}</span></div>
        <div class="row"><span>{{ $labels['cashier'] }}:</span><span>{{ $sale->cashier->name ?? '-' }}</span></div>
        <div class="row"><span>{{ $business['cash_register_name'] }}:</span><span>#{{ $sale->cash_register_id }}</span></div>
        <div class="row"><span>{{ $labels['internal_id'] }}:</span><span>#{{ $sale->id }}</span></div>
    </div>

    <div class="line"></div>

    <table class="items">
        <thead>
            <tr>
                <th>{{ $labels['product'] }}</th>
                <th class="right">{{ $labels['qty'] }}</th>
                <th class="right">{{ $labels['amount'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>
                        <span class="bold">{{ $item->product_name_snapshot }}</span><br>
                        @if($item->product_code_snapshot)
                            <span class="muted">{{ $item->product_code_snapshot }}</span><br>
                        @endif
                        <span>{{ $money($item->unit_price) }} {{ $labels['each'] }}</span>
                        @if((float) $item->discount > 0)
                            <br><span>{{ $labels['discount'] }}: {{ $money($item->discount) }}</span>
                        @endif
                    </td>
                    <td class="right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="right">{{ $money((float) $item->subtotal - (float) $item->discount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <div class="row"><span>Subtotal:</span><span>{{ $money($sale->subtotal) }}</span></div>
    <div class="row"><span>{{ $labels['discount'] }}:</span><span>{{ $money($sale->discount_total) }}</span></div>
    @if((float) $sale->igv > 0)
        <div class="row"><span>{{ $labels['tax'] }}:</span><span>{{ $money($sale->igv) }}</span></div>
    @endif
    <div class="row bold" style="font-size: 13px;"><span>{{ $labels['total'] }}:</span><span>{{ $money($sale->total) }}</span></div>

    <div class="line"></div>

    @foreach($sale->payments as $payment)
        <div class="row"><span>{{ $paymentLabels[$payment->payment_method] ?? $payment->payment_method }}:</span><span>{{ $money($payment->amount) }}</span></div>
        @if($payment->received_amount)
            <div class="row"><span>{{ $labels['received'] }}:</span><span>{{ $money($payment->received_amount) }}</span></div>
        @endif
        @if($payment->change_amount)
            <div class="row"><span>{{ $labels['change'] }}:</span><span>{{ $money($payment->change_amount) }}</span></div>
        @endif
        @if($business['thermal_show_payment_refs'] && $payment->operation_number)
            <div class="row"><span>{{ $labels['operation'] }}:</span><span>{{ $payment->operation_number }}</span></div>
        @endif
        @if($business['thermal_show_payment_refs'] && $payment->bank_name)
            <div class="row"><span>{{ $labels['bank'] }}:</span><span>{{ $payment->bank_name }}</span></div>
        @endif
    @endforeach

    <p class="notice">
        {{ $labels['notice'] }}.
    </p>
    @if($business['receipt_footer'])
        <p class="center" style="margin-top: 8px;">{{ $business['receipt_footer'] }}</p>
    @endif
    <script>
        const button = document.getElementById('printButton');
        const status = document.getElementById('printStatus');
        async function requestPrint() {
            button.disabled = true;
            status.textContent = @json($labels['requesting']);
            try {
                await fetch(@json(route('sales.print-request', $sale->id)), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                status.textContent = @json($labels['opened']);
                window.print();
            } catch (error) {
                status.textContent = @json($labels['failed']);
            } finally {
                button.disabled = false;
            }
        }
        button?.addEventListener('click', requestPrint);
        @if($autoPrint)
            window.addEventListener('load', requestPrint);
        @endif
    </script>
</body>
</html>
