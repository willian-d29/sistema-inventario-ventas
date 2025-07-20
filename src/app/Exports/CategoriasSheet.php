<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart,
    DataSeries,
    DataSeriesValues,
    Legend,
    PlotArea,
    Title
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoriasSheet implements FromArray, WithTitle, WithStyles, WithColumnFormatting, WithCharts, ShouldAutoSize
{
    protected array $data;

    public function __construct()
    {
        $categorias = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name as categoria',
                DB::raw('SUM(order_items.quantity) as total_vendidos'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_facturado')
            )
            ->groupBy('categories.name')
            ->orderByDesc('total_facturado')
            ->get();

        $this->data = [['Categoría', 'Cantidad Vendida', 'Total Facturado']];
        foreach ($categorias as $c) {
            $this->data[] = [$c->categoria, (int)$c->total_vendidos, (float)$c->total_facturado];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Categorías';
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = count($this->data);

        // Estilo encabezado
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f2f2f2'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ]
        ]);

        // Estilo datos
        $sheet->getStyle("A2:C{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => 'center',
            ]
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function charts(): array
    {
        $numRows = count($this->data);
        if ($numRows <= 1) return [];

        $labels = [new DataSeriesValues('String', "Categorías!A2:A{$numRows}", null, $numRows - 1)];
        $values = [new DataSeriesValues('Number', "Categorías!C2:C{$numRows}", null, $numRows - 1)];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($values) - 1),
            $labels,
            [],
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Distribución por Categoría');

        $chart = new Chart(
            'Categorías Más Rentables',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('E4');
        $chart->setBottomRightPosition('M20');

        return [$chart];
    }
}
