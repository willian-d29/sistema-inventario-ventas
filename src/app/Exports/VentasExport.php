<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\ResumenSheet;
use App\Exports\VentasSheet;
use App\Exports\ProductosSheet;
use App\Exports\ClientesSheet;
use App\Exports\CategoriasSheet;
use App\Exports\DashboardSheet;
use App\Exports\VentasMensualesSheet;

class VentasExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ResumenSheet(),
            new VentasSheet(),
            new ProductosSheet(),
            new ClientesSheet(),
            new CategoriasSheet(),
            new DashboardSheet(),
            new VentasMensualesSheet()
        ];
    }
}
