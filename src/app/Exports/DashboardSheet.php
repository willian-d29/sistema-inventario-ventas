<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\{
    Chart,
    DataSeries,
    DataSeriesValues,
    Legend,
    PlotArea,
    Title
};

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithStyles;

class DashboardSheet implements FromArray, WithTitle, WithDrawings, WithCharts, WithStyles
{
    public function array(): array
    {
        return [
            ['KPI', 'Valor'],
            ['Total de Ventas', '=Resumen!B2'],
            ['Ganancia Total', '=Resumen!B3'],
            ['Órdenes Registradas', '=Resumen!B4'],
            ['Promedio x Orden', '=Resumen!B5'],
            ['Cliente Top', '=Clientes!A2'],
            ['Última Venta', '=Resumen!B8'],
        ];
    }

    public function title(): string
    {
        return 'Dashboard';
    }

    public function drawings(): array
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo Laratory');
        $drawing->setPath(public_path('assets/laratory-logo-zip-file/png/logo-no-background.png'));
        $drawing->setHeight(70);
        $drawing->setCoordinates('E1');

        return [$drawing];
    }

    public function styles(Worksheet $sheet)
    {
        // Título
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2F0D9'],
            ],
            'alignment' => ['horizontal' => 'center'],
        ]);

        // KPI etiquetas
        $sheet->getStyle('A2:A8')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'left'],
        ]);

        // KPI valores
        $sheet->getStyle('B2:B8')->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => 'center'],
        ]);

        // Bordes
        $sheet->getStyle('A1:B8')->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Ajuste de ancho
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Filtro
        $sheet->setAutoFilter('A1:B8');
    }

    public function charts(): array
    {
        return [
            $this->graficoProductos(),
            $this->graficoCategorias(),
        ];
    }

    protected function graficoProductos(): Chart
    {
        $labels = [new DataSeriesValues('String', 'Top Productos!A2:A11', null, 10)];
        $values = [new DataSeriesValues('Number', 'Top Productos!B2:B11', null, 10)];

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
        $title = new Title('Top Productos');

        $chart = new Chart(
            'Top Productos',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('D10');
        $chart->setBottomRightPosition('L26');

        return $chart;
    }

    protected function graficoCategorias(): Chart
    {
        $labels = [new DataSeriesValues('String', 'Categorías!A2:A11', null, 10)];
        $values = [new DataSeriesValues('Number', 'Categorías!C2:C11', null, 10)];

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
        $title = new Title('Ingresos por Categoría');

        $chart = new Chart(
            'Ingresos por Categoría',
            $title,
            $legend,
            $plotArea
        );

        $chart->setTopLeftPosition('D28');
        $chart->setBottomRightPosition('L45');

        return $chart;
    }
}
