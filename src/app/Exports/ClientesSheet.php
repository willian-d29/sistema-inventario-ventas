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
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;

class ClientesSheet implements FromArray, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize, WithCharts
{
    protected array $data;

    public function __construct()
    {
        $clientes = DB::table('users')
            ->leftJoin('orders', 'users.id', '=', 'orders.customer_id')
            ->where('users.role', 'cliente')
            ->select(
                'users.name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_pedidos'),
                DB::raw('SUM(orders.total) as total_gastado'),
                DB::raw('MAX(orders.created_at) as ultima_compra')
            )
            ->groupBy('users.name', 'users.email')
            ->orderByDesc('total_gastado')
            ->limit(10)
            ->get();

        $this->data = [['Cliente', 'Correo', 'Pedidos', 'Total Gastado', 'Última Compra']];
        foreach ($clientes as $c) {
            $this->data[] = [
                $c->name,
                $c->email,
                $c->total_pedidos ?? 0,
                $c->total_gastado ?? 0.0,
                $c->ultima_compra ? date('Y-m-d', strtotime($c->ultima_compra)) : 'Sin compras'
            ];
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Clientes';
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = count($this->data);

        $sheet->getStyle('A1:E1')->applyFromArray([
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
            ],
        ]);

        $sheet->getStyle("A2:E{$highestRow}")->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function charts(): array
    {
        $numRows = count($this->data);
        if ($numRows <= 1) return [];

        $labels = [new DataSeriesValues('String', "Clientes!A2:A{$numRows}", null, $numRows - 1)];
        $values = [new DataSeriesValues('Number', "Clientes!D2:D{$numRows}", null, $numRows - 1)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            [],
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR); // Barras horizontales

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('Clientes con más gasto');

        $chart = new Chart(
            'Clientes con más gasto',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('G4');
        $chart->setBottomRightPosition('P20');

        return [$chart];
    }
}
