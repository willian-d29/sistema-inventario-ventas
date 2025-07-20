<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart,
    DataSeries,
    DataSeriesValues,
    Legend,
    PlotArea,
    Title
};
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ResumenSheet implements FromArray, WithTitle, WithDrawings, WithCharts, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected array $data;

    public function __construct()
    {
        $totalVentas = Order::sum('total');
        $ganancia = Order::sum('profit');
        $totalOrdenes = Order::count();
        $promedioOrden = $totalOrdenes > 0 ? $totalVentas / $totalOrdenes : 0;
        $clientes = User::where('role', 'cliente')->count();
        $topProducto = Product::select('name')
            ->withSum('orderItems as vendidos', 'quantity')
            ->orderByDesc('vendidos')
            ->value('name') ?? 'N/A';
        $ultimaVenta = Order::latest('created_at')->value('created_at');

        $this->data = [
            ['Métrica', 'Valor'],
            ['Total de Ventas', $totalVentas],
            ['Ganancia Total', $ganancia],
            ['Total de Órdenes', $totalOrdenes],
            ['Promedio por Orden', round($promedioOrden, 2)],
            ['Clientes Registrados', $clientes],
            ['Producto más Vendido', $topProducto],
            ['Última Venta', $ultimaVenta ? date('Y-m-d', strtotime($ultimaVenta)) : 'Sin datos'],
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function drawings(): array
    {
        $drawing = new Drawing();
        $drawing->setName('Logo Laratory');
        $drawing->setDescription('Logo institucional');
        $drawing->setPath(public_path('assets/laratory-logo-zip-file/png/logo-no-background.png'));
        $drawing->setHeight(60);
        $drawing->setCoordinates('E1');

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = count($this->data);

        // Encabezado
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'd9e1f2'],
            ],
            'alignment' => ['horizontal' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ]
        ]);

        // Celdas restantes
        $sheet->getStyle("A2:B{$highestRow}")->applyFromArray([
            'alignment' => ['horizontal' => 'left'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ]
        ]);

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
    }

    public function columnFormats(): array
    {
        return [
            'B2' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'B3' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'B5' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function charts(): array
    {
        $labels = [new DataSeriesValues('String', 'Resumen!$A$2:$A$4', null, 3)];
        $values = [new DataSeriesValues('Number', 'Resumen!$B$2:$B$4', null, 3)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            [],
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Resumen Financiero');

        $chart = new Chart(
            'Resumen Financiero',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('D10');
        $chart->setBottomRightPosition('L26');

        return [$chart];
    }
}
