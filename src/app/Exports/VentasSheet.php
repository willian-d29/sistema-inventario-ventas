<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart,
    DataSeries,
    DataSeriesValues,
    Legend,
    PlotArea,
    Title
};

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;

class VentasSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize, WithCharts
{
    protected Collection $ventas;

    public function __construct()
    {
        $this->ventas = Order::select('id', 'order_number', 'total', 'profit', 'status', 'created_at')
            ->orderBy('created_at')
            ->get();
    }

    public function collection()
    {
        return $this->ventas;
    }

    public function headings(): array
    {
        return ['ID', 'N° Orden', 'Total', 'Ganancia', 'Estado', 'Fecha'];
    }

    public function title(): string
    {
        return 'Ventas';
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Encabezado
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EAF1']
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Celdas de contenido
        $sheet->getStyle("A2:F{$highestRow}")->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Totales
        $lastRow = $highestRow + 1;
        $sheet->setCellValue("B{$lastRow}", 'Totales:');
        $sheet->setCellValue("C{$lastRow}", "=SUM(C2:C{$highestRow})");
        $sheet->setCellValue("D{$lastRow}", "=SUM(D2:D{$highestRow})");

        $sheet->getStyle("B{$lastRow}:D{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDEBF7']
            ],
            'alignment' => ['horizontal' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Estilos condicionales
        for ($row = 2; $row <= $highestRow; $row++) {
            $ganancia = $sheet->getCell("D{$row}")->getValue();

            // Verde si ganancia >= 100
            if ($ganancia >= 100) {
                $sheet->getStyle("D{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'd4edda']],
                    'font' => ['color' => ['rgb' => '155724']]
                ]);
            }

            // Rojo si ganancia <= 0
            if ($ganancia <= 0) {
                $sheet->getStyle("D{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f8d7da']],
                    'font' => ['color' => ['rgb' => '721c24']]
                ]);
            }

            // Estado colores
            $estado = strtolower($sheet->getCell("E{$row}")->getValue());

            if ($estado === 'paid') {
                $sheet->getStyle("E{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '28a745'], 'bold' => true]
                ]);
            } elseif ($estado === 'unpaid') {
                $sheet->getStyle("E{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'dc3545'], 'bold' => true]
                ]);
            } elseif ($estado === 'partial_paid') {
                $sheet->getStyle("E{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => 'ffc107'], 'bold' => true]
                ]);
            }

            // Fecha reciente
            $fecha = $sheet->getCell("F{$row}")->getValue();
            if ($fecha && strtotime($fecha) >= strtotime('-5 days')) {
                $sheet->getStyle("F{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '007bff'], 'italic' => true]
                ]);
            }
        }
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'D' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function charts(): array
    {
        $numRows = $this->ventas->count();
        if ($numRows === 0) return [];

        $limit = min(15, $numRows);
        $start = 2;
        $end = $limit + 1;

        $labels = [new DataSeriesValues('String', "Ventas!B{$start}:B{$end}", null, $limit)];

        $seriesLabels = [
            new DataSeriesValues('String', '"Ventas"!C1', null, 1),
            new DataSeriesValues('String', '"Ventas"!D1', null, 1)
        ];

        $totalValues  = new DataSeriesValues('Number', "Ventas!C{$start}:C{$end}", null, $limit);
        $profitValues = new DataSeriesValues('Number', "Ventas!D{$start}:D{$end}", null, $limit);

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0, 1],
            $seriesLabels,
            $labels,
            [$totalValues, $profitValues]
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_TOP, null, false);
        $title = new Title('Comparativo: Total vs Ganancia');

        $chart = new Chart(
            'Comparativo de Ventas',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('H3');
        $chart->setBottomRightPosition('R22');

        return [$chart];
    }
}
