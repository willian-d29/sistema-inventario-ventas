<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart, DataSeries, DataSeriesValues, Legend, PlotArea, Title
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductosSheet implements FromArray, WithTitle, WithStyles, WithCharts, ShouldAutoSize
{
    protected array $data;

    public function __construct()
    {
        $productos = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_vendidos'))
            ->groupBy('products.name')
            ->orderByDesc('total_vendidos')
            ->limit(10)
            ->get();

        $this->data = [[' Producto', ' Cantidad Vendida']];
        foreach ($productos as $p) {
            $this->data[] = [$p->name, (int)$p->total_vendidos];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Top Productos';
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = count($this->data);

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CFE2FF'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ]
        ]);

        $sheet->getStyle("A2:B{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_HAIR],
            ],
            'alignment' => [
                'horizontal' => 'left',
            ]
        ]);

        // Ancho personalizado
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(18);
    }

    public function charts(): array
    {
        $numRows = count($this->data);
        if ($numRows <= 1) return [];

        $labels = [new DataSeriesValues('String', "Top Productos!A2:A{$numRows}", null, $numRows - 1)];
        $values = [new DataSeriesValues('Number', "Top Productos!B2:B{$numRows}", null, $numRows - 1)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            $labels,
            [],
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title(' Productos Más Vendidos');

        $chart = new Chart(
            'Productos Más Vendidos',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('D3');
        $chart->setBottomRightPosition('L20');

        return [$chart];
    }
}
