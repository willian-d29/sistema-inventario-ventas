<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VentasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::select('id', 'order_number', 'total', 'profit', 'status', 'created_at')->get();
    }

    public function headings(): array
    {
        return ['ID', 'N° Orden', 'Total', 'Ganancia', 'Estado', 'Fecha'];
    }
}
