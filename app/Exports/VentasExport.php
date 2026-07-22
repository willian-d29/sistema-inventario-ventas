<?php

namespace App\Exports;

use App\Enums\Transaction\PaymentMethodEnum;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VentasExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function collection()
    {
        $labels = collect(PaymentMethodEnum::options())->pluck('label', 'value');

        return $this->query()
            ->with(['cashier', 'payments', 'items'])
            ->latest('sold_at')
            ->get()
            ->map(function (Sale $sale) use ($labels) {
                $netProductIncome = (float) $sale->items->sum(fn ($item) => (float) $item->subtotal - (float) $item->discount);
                $grossProfit = (float) $sale->items->sum('gross_profit');

                return [
                    'id' => $sale->id,
                    'comprobante' => $sale->full_document_number,
                    'tipo' => $this->documentTypeLabel($sale->document_type),
                    'cajero' => $sale->cashier?->name ?: 'Sin cajero',
                    'metodos_pago' => $sale->payments
                        ->map(fn ($payment) => $labels[$payment->payment_method] ?? ucfirst($payment->payment_method))
                        ->unique()
                        ->join(' + '),
                    'ingreso_neto_productos' => $netProductIncome,
                    'costo' => (float) $sale->items->sum('cost_subtotal'),
                    'utilidad_bruta' => $grossProfit,
                    'margen_bruto' => $this->grossMargin($grossProfit, $netProductIncome),
                    'subtotal' => (float) $sale->subtotal,
                    'descuento' => (float) $sale->discount_total,
                    'igv' => (float) $sale->igv,
                    'total' => (float) $sale->total,
                    'estado' => $sale->status,
                    'fecha' => $sale->sold_at?->format('d/m/Y H:i'),
                ];
            });
    }

    public function headings(): array
    {
        return ['ID', 'Comprobante', 'Tipo', 'Cajero', 'Métodos de pago', 'Ingreso neto productos', 'Costo', 'Utilidad bruta', 'Margen bruto %', 'Subtotal', 'Descuento', 'IGV', 'Total', 'Estado', 'Fecha'];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"S/ "#,##0.00',
            'G' => '"S/ "#,##0.00',
            'H' => '"S/ "#,##0.00',
            'I' => '0.00"%"',
            'J' => '"S/ "#,##0.00',
            'K' => '"S/ "#,##0.00',
            'L' => '"S/ "#,##0.00',
            'M' => '"S/ "#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $tableRange = "A1:O{$highestRow}";

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($tableRange);

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DBE3EF'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow > 1) {
            $sheet->getStyle("F2:M{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A2:A{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N2:N{$highestRow}")
                ->getFont()
                ->getColor()
                ->setRGB('047857');
        }

        return [];
    }

    private function query(): Builder
    {
        return Sale::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($this->filters['date_from'] ?? null, function (Builder $query, string $date) {
                $query->where('sold_at', '>=', Carbon::parse($date)->startOfDay());
            })
            ->when($this->filters['date_to'] ?? null, function (Builder $query, string $date) {
                $query->where('sold_at', '<=', Carbon::parse($date)->endOfDay());
            })
            ->when($this->filters['cashier_id'] ?? null, fn (Builder $query, int $cashierId) => $query->where('cashier_id', $cashierId))
            ->when($this->filters['payment_method'] ?? null, function (Builder $query, string $method) {
                $query->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('payment_method', $method));
            })
            ->when($this->filters['product_id'] ?? null, function (Builder $query, int $productId) {
                $query->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('product_id', $productId));
            })
            ->when($this->filters['document_type'] ?? null, function (Builder $query, string $documentType) {
                $query->where('document_type', $documentType);
            });
    }

    private function documentTypeLabel(string $type): string
    {
        return match ($type) {
            'receipt' => 'Boleta',
            'invoice' => 'Factura',
            default => 'Comprobante',
        };
    }

    private function grossMargin(float $grossProfit, float $netIncome): float
    {
        if ($netIncome <= 0) {
            return 0.0;
        }

        return round(($grossProfit / $netIncome) * 100, 2);
    }
}
