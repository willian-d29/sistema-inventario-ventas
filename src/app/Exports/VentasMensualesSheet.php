<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class VentasMensualesSheet implements FromArray, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected array $data;

    public function __construct()
    {
        // Agrupar ventas por mes (formato YYYY-MM)
        $ventas = DB::table('orders')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, SUM(total) as total_mensual")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get();

        $this->data = [['Mes', 'Total Ventas']];
        foreach ($ventas as $v) {
            $this->data[] = [$v->mes, round($v->total_mensual, 2)];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'VentasMensuales';
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = count($this->data);

        // Encabezado
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ddebf7'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => 'center',
            ]
        ]);

        // Datos
        $sheet->getStyle("A2:B{$highestRow}")->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Filtro automático
        $sheet->setAutoFilter("A1:B{$highestRow}");
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }
}
